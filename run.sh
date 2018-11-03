#!/bin/sh

mkdir -p build
[ -d build/public_html ] || (curl https://wordpress.org/latest.tar.gz | tar -xzvf - && mv wordpress build/public_html)
mkdir -p build/private_files
REPO_ROOT="$(pwd)"
PUBLIC_ROOT="$(pwd)/build/public_html"
SECRETS_ROOT="$(pwd)/build/private_files"

echo "<?php phpinfo(); ?>" > $PUBLIC_ROOT/info.php

echo "{\"public-root\":\"$PUBLIC_ROOT\",\"secrets-root\":\"$SECRETS_ROOT\",\"branch\":\"dev\"}" > $SECRETS_ROOT/deployment.json

cp -fT $REPO_ROOT/server_config.php $SECRETS_ROOT/server_config.php

ln -sfn $REPO_ROOT/olz-plugin/ $PUBLIC_ROOT/wp-content/plugins/olz-plugin
ln -sfn $REPO_ROOT/olz-theme/ $PUBLIC_ROOT/wp-content/themes/olz-theme
ln -sfn $REPO_ROOT/common/ $PUBLIC_ROOT/_common
ln -sfn $REPO_ROOT/_sql_migration.php $PUBLIC_ROOT/_sql_migration.php

ln -sfn $REPO_ROOT/olz-plugin/on_mail.php $PUBLIC_ROOT/_on_mail.php
ln -sfn $REPO_ROOT/olz-plugin/on_telegram.php $PUBLIC_ROOT/_on_telegram.php
ln -sfn $REPO_ROOT/olz-plugin/on_tick.php $PUBLIC_ROOT/_on_tick.php

ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/server_config_link.php
ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/wp-content/plugins/olz-plugin/server_config_link.php
ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/wp-content/themes/olz-theme/server_config_link.php

ln -sfn $PUBLIC_ROOT/_common/ $PUBLIC_ROOT/wp-content/plugins/olz-plugin/common_link

php $REPO_ROOT/webhook_simulator.php "$SECRETS_ROOT" "$PUBLIC_ROOT" 127.0.0.1:30270 &
SIMULATOR_PID=$!
echo "Simulator PID: $SIMULATOR_PID"
php -S 127.0.0.1:30270 -t $PUBLIC_ROOT
kill -9 $SIMULATOR_PID
