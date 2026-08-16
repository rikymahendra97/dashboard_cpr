#!/bin/bash
# Jalankan script ini dari dalam folder application/controllers
# Misal: cd /var/www/dashboard_cpr/application/controllers && bash fix_ci3_controllers.sh

for f in *.php; do
    # ambil huruf pertama kapital + sisanya tetap
    newname="$(tr '[:lower:]' '[:upper:]' <<< ${f:0:1})${f:1}"
    if [ "$f" != "$newname" ]; then
        echo "Renaming $f -> $newname"
        mv "$f" "$newname"
    fi
done

