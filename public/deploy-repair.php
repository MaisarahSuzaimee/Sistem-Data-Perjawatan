<?php
/**
 * One-time repair helper for production when git pull is blocked by local
 * changes (usually package-lock.json left dirty by npm install).
 *
 * Upload this file into public/ via Hestia File Manager if deploy.php cannot
 * pull yet, then trigger it once with the same deploy token:
 *
 *   curl -fsS -H "X-Deploy-Token: YOUR_TOKEN" https://mystaff.kdh.moh.gov.my/deploy-repair.php
 *
 * After a successful repair, re-run the GitHub Actions deploy workflow.
 */

const SECRET_TOKEN = '4d1e3b5fa1a37b78e26dff4ba59762627ad921907cdb4f4aafe68c0bcd549171';
const REPO_DIR = __DIR__ . '/..';
const LOG_FILE = __DIR__ . '/../storage/logs/deploy.log';

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
        if (function_exists($fn) && ! in_array($fn, $disabled, true)) {
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
            if (! is_resource($proc)) {
                return [1, 'proc_open failed to start command'];
            }
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            return [proc_close($proc), trim($stdout . "\n" . $stderr)];

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

$token = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? ($_GET['token'] ?? '');
if (! is_string($token) || ! hash_equals(SECRET_TOKEN, $token) || SECRET_TOKEN === 'CHANGE-ME-TO-A-LONG-RANDOM-STRING') {
    respond(403, 'Forbidden');
}

$executor = pickExecutor();
if ($executor === null) {
    respond(500, 'No usable PHP shell-execution function is available on this host.');
}

$repoDir = realpath(REPO_DIR);
if ($repoDir === false || ! is_dir($repoDir . '/.git')) {
    respond(500, 'REPO_DIR does not point at a git repository: ' . REPO_DIR);
}

[$statusCode, $statusOut] = runGit($executor, $repoDir, 'status --porcelain');
if ($statusCode !== 0) {
    logLine('ERROR: deploy-repair status failed: ' . $statusOut);
    respond(500, "git status failed:\n" . $statusOut);
}

[$resetCode, $resetOut] = runGit($executor, $repoDir, 'reset --hard HEAD');
if ($resetCode !== 0) {
    logLine('ERROR: deploy-repair reset failed: ' . $resetOut);
    respond(500, "git reset --hard HEAD failed:\n" . $resetOut);
}

[$headCode, $headOut] = runGit($executor, $repoDir, 'rev-parse HEAD');
$head = $headCode === 0 ? trim($headOut) : '(unknown)';

logLine('deploy-repair cleared local changes at ' . substr($head, 0, 12));

respond(200, implode("\n", [
    'Local git changes cleared.',
    'Commit: ' . $head,
    '',
    'Previous dirty status:',
    trim($statusOut) === '' ? '(clean)' : $statusOut,
    '',
    'git reset --hard HEAD output:',
    $resetOut,
    '',
    'Next: re-run the GitHub Actions deploy workflow.',
]));
