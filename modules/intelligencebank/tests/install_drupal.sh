#!/usr/bin/env bash

prepare()
{
    apt-get update && apt-get install -qy git libjpeg62-turbo-dev libpng-dev zip unzip
    docker-php-ext-install gd
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
}

function installDrupal()
{
    CURRENT_DIR=${PWD}

    if [ -z ${1+x} ]; then
        echo "Please specify Drupal version as first argument"
        return 1
    else
        DRUPAL_VERSION=${1}
    fi
    if [ ! -z ${2+x} ]; then
        DRUPAL_CORE_VERSION=${2}
    fi

    if [ -z ${DRUPAL_INSTALL_TYPE+x} ]; then
        DRUPAL_INSTALL_TYPE="composer"
    fi

    # composer installation
    if [ "${DRUPAL_INSTALL_TYPE}" == "composer" ]; then
        INSTALLATION_DIR="${INSTALL_PATH}/${DRUPAL_VERSION}"
        if [ -z ${DRUPAL_CORE_VERSION} ]; then
            echo "Create Drupal project ${DRUPAL_VERSION} in ${INSTALLATION_DIR}"
        else
            INSTALLATION_DIR="${INSTALLATION_DIR}_${DRUPAL_CORE_VERSION}"
            echo "Create Drupal project ${DRUPAL_VERSION} (core ${DRUPAL_CORE_VERSION}) in ${INSTALLATION_DIR}"
        fi

        if [ -d "${INSTALLATION_DIR}" ]; then
            echo "Directory ${INSTALLATION_DIR} exists. Exit"
            return 1
        else
            echo "create-project in ${INSTALLATION_DIR}"
            composer create-project drupal-composer/drupal-project:${DRUPAL_VERSION} ${INSTALLATION_DIR} --no-interaction
        fi

        if [ ! -d ${INSTALLATION_DIR} ]; then
            echo "Installation directory not exists (${INSTALLATION_DIR})"
        else
            cd ${INSTALLATION_DIR}
        fi

        if [ ! -z ${DRUPAL_CORE_VERSION} ]; then
            echo "Set Drupal core to ${DRUPAL_CORE_VERSION}"
            composer require --no-update drupal/core:${DRUPAL_CORE_VERSION}
            composer require --dev --no-update webflo/drupal-core-require-dev:${DRUPAL_CORE_VERSION}
            composer update drupal/core webflo/drupal-core-require-dev --with-dependencies
        fi

        vendor/bin/drush site-install standard --db-url=sqlite://sites/example.com/files/.ht.sqlite install_configure_form.enable_update_status_emails=NULL -y

        cd ${CURRENT_DIR}
        return 0
    fi
}

function installDrupalModule()
{
    CURRENT_DIR=${PWD}

    if [ -z ${1+x} ]; then
        echo "Please specify module machine name as first argument"
        return 1
    else
        DRUPAL_MODULE_MACHINE_NAME=${1}
    fi
    if [ -z ${2+x} ]; then
        echo "Please specify module machine name as second argument"
        return 1
    else
        DRUPAL_MODULE_PACKAGE=${2}
    fi
    if [ -z ${3+x} ]; then
        echo "Please specify Drupal version as third argument"
        return 1
    else
        DRUPAL_VERSION=${3}
    fi
    if [ ! -z ${4+x} ]; then
        DRUPAL_CORE_VERSION=${4}
    fi

    INSTALLATION_DIR="${INSTALL_PATH}/${DRUPAL_VERSION}"
    if [ ! -z ${DRUPAL_CORE_VERSION} ]; then
        INSTALLATION_DIR="${INSTALLATION_DIR}_${DRUPAL_CORE_VERSION}"
    fi
    if [ ! -d ${INSTALLATION_DIR} ]; then
        echo "Can't find Drupal in (${INSTALLATION_DIR})"
    else
        cd ${INSTALLATION_DIR}
    fi

    echo "Install module ${DRUPAL_MODULE_MACHINE_NAME} (${DRUPAL_MODULE_PACKAGE})"
    case ${DRUPAL_MODULE_MACHINE_NAME} in
        ib_dam_media|ib_dam_wysiwyg)
            composer require drupal/intelligencebank
            mkdir -p "${INSTALLATION_DIR}/web/modules/custom"
            cp -r "${CURRENT_DIR}/modules/${DRUPAL_MODULE_MACHINE_NAME}" "${INSTALLATION_DIR}/web/modules/custom/${DRUPAL_MODULE_MACHINE_NAME}"
            ;;
        *)
            composer require ${DRUPAL_MODULE_PACKAGE}
            ;;
    esac

    echo "Enable module ${DRUPAL_MODULE_MACHINE_NAME}"
    vendor/bin/drush en ${DRUPAL_MODULE_MACHINE_NAME} -y

    cd ${CURRENT_DIR}
    return 0
}

