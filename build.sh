#!/bin/sh

PUBLIC_ROOT=0
SECRETS_ROOT=0
BRANCH=0

while [[ $1 == -* ]]; do
  case "$1" in
    -p|--public-root) PUBLIC_ROOT=$2; shift 2;;
    -s|--secrets-root) SECRETS_ROOT=$2; shift 2;;
    -b|--branch) BRANCH=$2; shift 2;;
    --) shift; break;;
    -*) echo "invalid option: $1" 1>&2; show_help; exit 1;;
  esac
done

if [[ $PUBLIC_ROOT != 0 && $SECRETS_ROOT != 0 && $DEPLOYMENT != 0 ]]; then
  mkdir -p $SECRETS_ROOT

  echo "{\"public-root\":\"$PUBLIC_ROOT\",\"secrets-root\":\"$SECRETS_ROOT\",\"branch\":\"$BRANCH\"}" > $SECRETS_ROOT/deployment.json

  cp -fT server_config.php $SECRETS_ROOT/server_config.php

  cp -fRT olz-plugin $PUBLIC_ROOT/wp-content/plugins/olz-plugin
  cp -fRT olz-theme $PUBLIC_ROOT/wp-content/themes/olz-theme
  cp -fRT common $PUBLIC_ROOT/_common
  cp -fT _sql_migration.php $PUBLIC_ROOT/_sql_migration.php

  ln -sfn $PUBLIC_ROOT/wp-content/plugins/olz-plugin/on_mail.php $PUBLIC_ROOT/_on_mail.php
  ln -sfn $PUBLIC_ROOT/wp-content/plugins/olz-plugin/on_telegram.php $PUBLIC_ROOT/_on_telegram.php
  ln -sfn $PUBLIC_ROOT/wp-content/plugins/olz-plugin/on_tick.php $PUBLIC_ROOT/_on_tick.php

  ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/server_config_link.php
  ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/wp-content/plugins/olz-plugin/server_config_link.php
  ln -sfn $SECRETS_ROOT/server_config.php $PUBLIC_ROOT/wp-content/themes/olz-theme/server_config_link.php

  ln -sfn $PUBLIC_ROOT/_common $PUBLIC_ROOT/wp-content/plugins/olz-plugin/common_link

fi
