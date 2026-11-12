<?php
/**
 * Deploy trigger: check GitHub for a new commit, git pull only if found.
 * Upload via Hestia File Manager into public/. Replace SECRET_TOKEN below
 * before uploading. Trigger with: https://your-domain/deploy.php?token=YOUR_TOKEN
 */

// ---- Config ---------------------------------------------------------

// Replace with your own random string any time you want to rotate it
// (also update DEPLOY_TOKEN secret on GitHub to match).
const SECRET_TOKEN = '4d1e3b5fa1a37b78e26dff4ba59762627ad921907cdb4f4aafe68c0bcd549171';

// Absolute path to the folder containing .git. Default assumes this file
// sits in public/ and the repo root is one level up.
const REPO_DIR = __DIR__ . '/..';

// Branch to track. Production always deploys from main.
const BRANCH = 'main';

// Log file, kept outside the web root (not downloadable).
const LOG_FILE = __DIR__ . '/../storage/logs/deploy.log';

// Run `composer install` after a pull, but only when composer.json/lock
// actually changed in that pull (skip otherwise — nothing to install).
const RUN_COMPOSER_INSTALL = true;

// Run `npm install && npm run build` after a pull, but only when
// package.json/lock or resources/ changed in that pull.
const RUN_NPM_BUILD = true;

// Run `php artisan migrate --force` after a successful pull.
const RUN_MIGRATIONS = true;

// Extra absolute paths to try for each binary, in case it's installed but
// not on the web server user's PATH. First working one wins; 'command -v'
// (searched with an expanded PATH) is tried before these.
const PHP_BIN_CANDIDATES = ['php', '/usr/bin/php', '/usr/local/bin/php'];
const COMPOSER_BIN_CANDIDATES = ['composer', '/usr/local/bin/composer', '/usr/bin/composer', '/opt/cpanel/composer/bin/composer'];
const NPM_BIN_CANDIDATES = ['npm', '/usr/local/bin/npm', '/usr/bin/npm'];

// ---- Helpers ----------------------------------------------------------

function respond(int $httpCode, string $text): void
{
    http_response_code($httpCode);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text . "\n";
    exit;
}

function logLine(string $line): void
{
    $dir = dirname(LOG_FILE);
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $line . "\n", FILE_APPEND);
    }
}

function disabledFunctions(): array
{
    return array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
}

function pickExecutor(): ?string
{
    $disabled = disabledFunctions();
    foreach (['proc_open', 'exec', 'shell_exec', 'system', 'passthru'] as $fn) {
        if (function_exists($fn) && !in_array($fn, $disabled, true)) {
            return $fn;
        }
    }
    return null;
}

function runCmd(string $executor, string $cmd): array
{
    switch ($executor) {
        case 'proc_open':
            $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($proc)) {
                return [1, 'proc_open failed to start command'];
            }
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($proc);
            return [$exitCode, trim($stdout . "\n" . $stderr)];

        case 'exec':
            exec($cmd . ' 2>&1', $output, $exitCode);
            return [$exitCode, implode("\n", $output)];

        case 'shell_exec':
            $raw = (string) shell_exec($cmd . ' 2>&1; echo "___EXIT:$?"');
            if (preg_match('/___EXIT:(\d+)\s*$/', $raw, $m)) {
                return [(int) $m[1], trim(preg_replace('/___EXIT:\d+\s*$/', '', $raw))];
            }
            return [1, trim($raw) . "\n(could not determine exit code)"];

        case 'system':
            ob_start();
            system($cmd . ' 2>&1', $exitCode);
            return [$exitCode, ob_get_clean()];

        case 'passthru':
            ob_start();
            passthru($cmd . ' 2>&1', $exitCode);
            return [$exitCode, ob_get_clean()];

        default:
            return [1, 'No shell executor available'];
    }
}

function runGit(string $executor, string $repoDir, string $args): array
{
    return runCmd($executor, 'git -C ' . escapeshellarg($repoDir) . ' ' . $args);
}

function runInDir(string $executor, string $dir, string $cmd): array
{
    return runCmd($executor, 'cd ' . escapeshellarg($dir) . ' && ' . $cmd);
}

function changedFiles(string $executor, string $repoDir, string $oldSha, string $newSha): array
{
    [$code, $out] = runGit($executor, $repoDir, 'diff --name-only ' . escapeshellarg($oldSha) . ' ' . escapeshellarg($newSha));
    if ($code !== 0) {
        return [];
    }
    return array_filter(array_map('trim', explode("\n", $out)));
}

function anyPathMatches(array $changed, array $needles): bool
{
    foreach ($changed as $file) {
        foreach ($needles as $needle) {
            if (str_starts_with($file, $needle)) {
                return true;
            }
        }
    }
    return false;
}

// Resolves a binary name to a usable path: tries `command -v` with a
// widened PATH first, then falls back to checking absolute candidates
// directly on disk (works even with all shell functions disabled).
function resolveBinary(string $executor, string $name, array $candidates): array
{
    $extraPath = '/usr/local/bin:/usr/bin:/bin:/opt/cpanel/composer/bin';
    [$code, $out] = runCmd($executor, "PATH=\"{$extraPath}:\$PATH\" command -v " . escapeshellarg($name) . ' 2>&1');
    if ($code === 0 && trim($out) !== '') {
        $lines = explode("\n", trim($out));
        return [trim($lines[0]), ''];
    }

    foreach ($candidates as $path) {
        if ($path !== $name && is_file($path) && is_executable($path)) {
            return [$path, ''];
        }
    }

    return [null, "not found via 'command -v {$name}' or candidate paths: " . implode(', ', $candidates)];
}

// ---- Auth ---------------------------------------------------------------

$token = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? ($_GET['token'] ?? '');
if (!is_string($token) || !hash_equals(SECRET_TOKEN, $token) || SECRET_TOKEN === 'CHANGE-ME-TO-A-LONG-RANDOM-STRING') {
    respond(403, 'Forbidden');
}

// composer install / npm run build can take a while — give the request
// more room than the host's default max_execution_time.
@set_time_limit(300);
@ignore_user_abort(true);

// ---- Environment guards ---------------------------------------------------

$executor = pickExecutor();
if ($executor === null) {
    $disabled = disabledFunctions();
    respond(500, implode("\n", [
        'No usable PHP shell-execution function is available on this host.',
        'Tried: proc_open, exec, shell_exec, system, passthru — all disabled.',
        'disable_functions on this host: ' . (empty($disabled) ? '(empty, but functions still unavailable)' : implode(', ', $disabled)),
        '',
        'Ask hosting/Hestia admin to enable at least one of proc_open or exec',
        'for this domain\'s PHP (Hestia: Web > domain > PHP-FPM template, or',
        'a custom php.ini for this domain). If that is not possible, a',
        'cron-job-based fallback (triggered on a timer instead of over HTTP)',
        'can be built instead, since cron runs outside PHP\'s restrictions.',
    ]));
}

$repoDir = realpath(REPO_DIR);
if ($repoDir === false || !is_dir($repoDir . '/.git')) {
    respond(500, 'REPO_DIR does not point at a git repository: ' . REPO_DIR);
}

// Defensive check: warn if .git ended up inside the web-served directory
// (i.e. this script's own folder is the repo root, not public/).
if (realpath(__DIR__) === $repoDir) {
    logLine('WARNING: deploy.php is running from the same directory as .git — .git may be web-exposed. Check the Hestia Document Root.');
}

// ---- Determine branch -----------------------------------------------------

$branch = BRANCH;
if ($branch === null) {
    [$code, $out] = runGit($executor, $repoDir, 'rev-parse --abbrev-ref HEAD');
    if ($code !== 0) {
        logLine('ERROR: could not detect current branch: ' . $out);
        respond(500, "Could not detect current branch:\n" . $out);
    }
    $branch = trim($out);
}

// ---- Fetch + compare -------------------------------------------------------

[$code, $out] = runGit($executor, $repoDir, 'fetch origin ' . escapeshellarg($branch) . ' --quiet');
if ($code !== 0) {
    logLine("ERROR: fetch failed on branch {$branch}: " . $out);
    respond(500, "git fetch failed:\n" . $out);
}

[$code, $localSha] = runGit($executor, $repoDir, 'rev-parse HEAD');
if ($code !== 0) {
    logLine('ERROR: could not read local HEAD: ' . $localSha);
    respond(500, "Could not read local HEAD:\n" . $localSha);
}
$localSha = trim($localSha);

[$code, $remoteSha] = runGit($executor, $repoDir, 'rev-parse origin/' . escapeshellarg($branch));
if ($code !== 0) {
    logLine("ERROR: could not read origin/{$branch}: " . $remoteSha);
    respond(500, "Could not read origin/{$branch}:\n" . $remoteSha);
}
$remoteSha = trim($remoteSha);

if ($localSha === $remoteSha) {
    logLine("Up to date on {$branch} at " . substr($localSha, 0, 12));
    respond(200, "Already up to date on {$branch}.\nCommit: {$localSha}");
}

// ---- New commit found: pull -------------------------------------------------

[$code, $pullOut] = runGit($executor, $repoDir, 'pull origin ' . escapeshellarg($branch));
if ($code !== 0) {
    logLine("ERROR: pull failed on {$branch} ({$localSha} -> {$remoteSha}): " . $pullOut);
    respond(500, "git pull failed:\n" . $pullOut);
}

logLine("Updated {$branch}: " . substr($localSha, 0, 12) . ' -> ' . substr($remoteSha, 0, 12));

$responseLines = [
    "Updated {$branch}.",
    "Old commit: {$localSha}",
    "New commit: {$remoteSha}",
    '',
    'git pull output:',
    $pullOut,
];

// From here on, code on disk is already updated even if a step below
// fails — check deploy.log for exactly which step failed.

$changed = changedFiles($executor, $repoDir, $localSha, $remoteSha);

// ---- composer install (only if composer.json/lock changed) -------------

if (RUN_COMPOSER_INSTALL) {
    if (!anyPathMatches($changed, ['composer.json', 'composer.lock'])) {
        $responseLines[] = '';
        $responseLines[] = 'composer install: skipped (composer.json/lock unchanged).';
    } else {
        [$composerPath, $diag] = resolveBinary($executor, 'composer', COMPOSER_BIN_CANDIDATES);
        if ($composerPath === null) {
            logLine("WARNING: composer install skipped after updating to {$remoteSha} — {$diag}");
            $responseLines[] = '';
            $responseLines[] = "composer install SKIPPED — composer {$diag}";
            $responseLines[] = 'Fix: set the real path in COMPOSER_BIN_CANDIDATES in deploy.php.';
        } else {
            [$cCode, $cOut] = runInDir($executor, $repoDir, escapeshellarg($composerPath) . ' install --no-dev --optimize-autoloader --no-interaction');
            if ($cCode !== 0) {
                logLine("ERROR: composer install failed after updating to {$remoteSha}: " . $cOut);
                $responseLines[] = '';
                $responseLines[] = 'composer install FAILED:';
                $responseLines[] = $cOut;
                respond(500, implode("\n", $responseLines));
            }
            logLine("composer install ok after updating to " . substr($remoteSha, 0, 12));
            $responseLines[] = '';
            $responseLines[] = 'composer install output:';
            $responseLines[] = $cOut;
        }
    }
}

// ---- npm install + build (only if frontend files changed) --------------

if (RUN_NPM_BUILD) {
    if (!anyPathMatches($changed, ['package.json', 'package-lock.json', 'resources/', 'vite.config'])) {
        $responseLines[] = '';
        $responseLines[] = 'npm build: skipped (no frontend-relevant files changed).';
    } else {
        [$npmPath, $diag] = resolveBinary($executor, 'npm', NPM_BIN_CANDIDATES);
        if ($npmPath === null) {
            logLine("WARNING: npm build skipped after updating to {$remoteSha} — {$diag}");
            $responseLines[] = '';
            $responseLines[] = "npm build SKIPPED — npm {$diag}";
            $responseLines[] = 'Fix: set the real path in NPM_BIN_CANDIDATES in deploy.php.';
        } else {
            [$nCode, $nOut] = runInDir($executor, $repoDir, escapeshellarg($npmPath) . ' install && ' . escapeshellarg($npmPath) . ' run build');
            if ($nCode !== 0) {
                logLine("ERROR: npm build failed after updating to {$remoteSha}: " . $nOut);
                $responseLines[] = '';
                $responseLines[] = 'npm install / npm run build FAILED:';
                $responseLines[] = $nOut;
                respond(500, implode("\n", $responseLines));
            }
            logLine("npm build ok after updating to " . substr($remoteSha, 0, 12));
            $responseLines[] = '';
            $responseLines[] = 'npm run build output:';
            $responseLines[] = $nOut;
        }
    }
}

// ---- Migrations ---------------------------------------------------------

if (RUN_MIGRATIONS) {
    [$phpPath, $diag] = resolveBinary($executor, 'php', PHP_BIN_CANDIDATES);
    if ($phpPath === null) {
        logLine("ERROR: migrate skipped after updating to {$remoteSha} — {$diag}");
        $responseLines[] = '';
        $responseLines[] = "php artisan migrate SKIPPED — php {$diag}";
        respond(500, implode("\n", $responseLines));
    }

    [$migCode, $migOut] = runInDir($executor, $repoDir, escapeshellarg($phpPath) . ' artisan migrate --force');
    if ($migCode !== 0) {
        logLine("ERROR: migrate failed after updating to {$remoteSha}: " . $migOut);
        $responseLines[] = '';
        $responseLines[] = 'php artisan migrate --force FAILED:';
        $responseLines[] = $migOut;
        respond(500, implode("\n", $responseLines));
    }
    logLine("Migrated after updating to " . substr($remoteSha, 0, 12));
    $responseLines[] = '';
    $responseLines[] = 'php artisan migrate --force output:';
    $responseLines[] = $migOut;
}

respond(200, implode("\n", $responseLines));
