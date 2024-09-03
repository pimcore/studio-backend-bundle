#!/bin/bash
###############################################################################
# Adding colors
###############################################################################
Green='\033[0;32m'        # Green
NC='\033[0m'              # No Color
Yellow='\033[0;33m'       # Yellow
Cyan='\033[0;36m'         # Cyan


###############################################################################
# Exit on error
###############################################################################
set -e
set +x

sudo rm -rf working-dir || true
printf " ${Green}%s${NC}\n" "Cleaned working directory"

# Create a working directory
mkdir working-dir || true
cd working-dir
printf " ${Green}%s${NC}\n" "Working directory created"
printf "%s${NC}\n"

docker pull docker.io/pimcore/pimcore:php8.2-latest
printf " ${Green}%s${NC}\n" "Pulled pimcore/pimcore:php8.2-latest"
printf "%s${NC}\n"

docker run \
  -u `id -u`:`id -g` --rm \
  -v `pwd`:/var/www/html \
  pimcore/pimcore:php8.3-latest \
  composer create-project pimcore/skeleton skeleton

printf " ${Green}%s${NC}\n" "Create pimcore/skeleton project"
printf "%s${NC}\n"

cd skeleton
cp ../../docker-compose.yaml .

printf " ${Green}%s${NC}\n" "Copied all the files to the working directory"
printf "%s${NC}\n"

docker compose down --remove-orphans
docker compose up -d

# Minimum stably has to be dev since this bundle requires some dev dependencies.
docker compose exec -T -- php composer config minimum-stability dev

printf " ${Green}%s${NC}\n" "Set minimum stability to dev"

# Platform version is not needed since we need to install som dev dependencies
docker compose exec -T -- php composer remove pimcore/platform-version

printf " ${Green}%s${NC}\n" "Removed platform version"
printf "%s${NC}\n"

sudo rm composer.lock || true

#docker compose exec -T -- php composer update

# Add the repository to the composer.json so that composer can symlink to the local bundle of the studio-backend
docker compose exec -T -- php composer config repositories.dev '{"type": "path", "url": "./../dev/pimcore/studio-backend-bundle", "options": { "symlink": true }}'

printf " ${Green}%s${NC}\n" "Added repository to composer.json"

docker compose exec -T -- php composer require -W \
    pimcore/studio-backend-bundle \

printf " ${Green}%s${NC}\n" "Symlinked studio backend bundle"
printf "%s${NC}\n"

# Run pimcore installation.
docker compose exec -T \
    -e PIMCORE_INSTALL_ADMIN_USERNAME=admin \
    -e PIMCORE_INSTALL_ADMIN_PASSWORD=pimcore \
    -e PIMCORE_INSTALL_MYSQL_USERNAME=pimcore \
    -e PIMCORE_INSTALL_MYSQL_PASSWORD=pimcore \
    -e PIMCORE_INSTALL_MYSQL_PORT=3306 \
    -e PIMCORE_INSTALL_MYSQL_HOST_SOCKET=db \
    -e PIMCORE_INSTALL_MYSQL_DATABASE=pimcore \
    -- \
    php vendor/bin/pimcore-install -n

# Fix permissions to run pimcore
sudo chown -R `id -g` ./config
sudo chmod -R 777 ./config
sudo chmod -R 777 ./var
sudo chmod -R 777 ./public


printf " ${Green}%s${NC}\n" "Installed Pimcore successfully"
printf "%s${NC}\n"


###############################################################################
# Change security.yaml Can be removed when studio is integrated in the skeleton
###############################################################################
printf "    ${Yellow}Change${NC} config settings in ${Cyan}${TARGET_DIR}/config/packages/security.yaml${NC}"
    sed -i '/firewalls:/a\        pimcore_studio: '\''%pimcore_studio_backend.firewall_settings%'\''' ./config/packages/security.yaml
    sed -i '/# Pimcore admin ACl  \/\/ DO NOT CHANGE!/a\        - \{ path: \^\/studio\/api\/\(docs\|docs.json\|translations\)\$, roles: PUBLIC_ACCESS \}\n        - \{ path: \^\/studio, roles: ROLE_PIMCORE_USER }' ./config/packages/security.yaml

rm ./config/bundles.php
cp ../../bundles.php ./config/bundles.php
cp ../../studio-config.yaml ./config/local/studio-config.yaml
cp ../../opensearch-config.yaml ./config/local/opensearch-config.yaml

docker compose exec -T php bin/console pimcore:bundle:install PimcoreStudioBackendBundle
docker compose exec -T php bin/console pimcore:bundle:install PimcoreGenericDataIndexBundle

docker compose exec -T php  bin/console generic-data-index:update:index -r


sed -i 's/command=php \/var\/www\/html\/bin\/console messenger:consume/& pimcore_generic_data_index_queue scheduler_generic_data_index/' ./.docker/supervisord.conf
docker compose restart --no-deps supervisord