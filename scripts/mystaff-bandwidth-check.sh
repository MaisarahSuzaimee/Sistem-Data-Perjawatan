# Mystaff bandwidth diagnosis — paste into SSH at ~/web/mystaff.kdh.moh.gov.my or public_html
set -e
SITE="$HOME/web/mystaff.kdh.moh.gov.my"
cd "$SITE"

echo "=== Site folders ==="
du -sh logs stats document_errors public_html 2>/dev/null || true
ls -lah logs stats 2>/dev/null || true

echo
echo "=== Access / error logs (largest) ==="
find logs stats /var/log/httpd /var/log/apache2 /var/log/nginx "$HOME/logs" -type f 2>/dev/null \
  | head -5
ls -lhS logs 2>/dev/null | head -20
ls -lhS "$HOME/logs" 2>/dev/null | head -20

# Prefer current access log if present
ACCESS=""
for f in \
  logs/mystaff.kdh.moh.gov.my.bytes \
  logs/mystaff.kdh.moh.gov.my.log \
  logs/access.log \
  logs/access_log \
  "$HOME/logs/mystaff.kdh.moh.gov.my.log" \
  "$HOME/logs/access.log"
 do
  if [ -f "$f" ]; then ACCESS="$f"; break; fi
done

# Also pick newest *.log under logs
if [ -z "$ACCESS" ]; then
  ACCESS=$(ls -1t logs/*access* logs/*.log 2>/dev/null | head -1 || true)
fi

echo
echo "ACCESS_LOG=${ACCESS:-NONE}"

if [ -n "$ACCESS" ] && [ -f "$ACCESS" ]; then
  echo
  echo "=== Top URLs by transfer (approx bytes) ==="
  # Combined / common DA format: ... "METHOD URL PROTO" status bytes
  awk -v OFS='\t' '
    {
      # request is usually field 6 or quoted; bytes often $10
      bytes=$10
      if (bytes+0!=bytes) bytes=0
      # extract URL from "GET /path HTTP/1.1"
      if (match($0, /"(GET|POST|PUT|DELETE|HEAD|OPTIONS) ([^ ]+)/, m)) {
        url=m[2]
        method=m[1]
      } else { url="?"; method="?" }
      # strip query for grouping
      split(url, a, "?"); path=a[1]
      b[path]+=bytes
      c[path]++
      tb+=bytes
    }
    END {
      for (p in b) printf "%d\t%d\t%s\n", b[p], c[p], p
    }
  ' "$ACCESS" 2>/dev/null | sort -nr | head -25 | awk -F'\t' '{
      mb=$1/1024/1024
      printf "%8.1f MB  %7d hits  %s\n", mb, $2, $3
    }'

  echo
  echo "=== Top IPs by transfer ==="
  awk '{
      bytes=$10; if (bytes+0!=bytes) bytes=0
      b[$1]+=bytes; c[$1]++
    } END {
      for (ip in b) printf "%d\t%d\t%s\n", b[ip], c[ip], ip
    }' "$ACCESS" 2>/dev/null | sort -nr | head -15 | awk -F'\t' '{
      mb=$1/1024/1024
      printf "%8.1f MB  %7d hits  %s\n", mb, $2, $3
    }'

  echo
  echo "=== Livewire / Filament / assets share ==="
  awk '{
      bytes=$10; if (bytes+0!=bytes) bytes=0
      if (match($0, /"(GET|POST) ([^ ]+)/, m)) {
        path=m[2]; split(path,a,"?"); p=a[1]
      } else next
      if (p ~ /livewire/) { lw+=bytes; lwc++ }
      else if (p ~ /filament|\/css\/|\/js\/|build\/assets|\.js|\.css|\.woff/) { as+=bytes; asc++ }
      else if (p ~ /export|download|\.xlsx|\.pdf|\.zip/) { ex+=bytes; exc++ }
      else { ot+=bytes; otc++ }
      tb+=bytes
    } END {
      printf "livewire: %.1f MB (%d)\nassets:   %.1f MB (%d)\nexports:  %.1f MB (%d)\nother:    %.1f MB (%d)\nTOTAL:    %.1f MB\n",
        lw/1024/1024,lwc+0, as/1024/1024,asc+0, ex/1024/1024,exc+0, ot/1024/1024,otc+0, tb/1024/1024
    }' "$ACCESS" 2>/dev/null
fi

echo
echo "=== DirectAdmin bandwidth files (if any) ==="
ls -lhS stats 2>/dev/null | head -20
find stats -type f \( -name "*usage*" -o -name "*.awstats*" -o -name "*bandwidth*" \) 2>/dev/null | head
