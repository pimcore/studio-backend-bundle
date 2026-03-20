---
title: API Testing
description: Running and writing tests for Studio Backend endpoints.
---

# Api Testing

## Set up local test environment
We provide a shell script to set up a local test environment. This script will install a fresh Pimcore instance and install the Studio Backend Bundle with all its dependencies. The script is located in the `tests/Api` directory.

```bash
cd tests/Api
sudo chmod +x boot-test-env.sh
./boot-test-env.sh
```


### Credentials

- **Pimcore admin user:** `admin`
- **Pimcore admin password:** `admin`
- **Database user:** `pimcore`
- **Database password:** `ROOT`
- **Database name:** `pimcore`
- **Opensearch Dashboard user:** `admin`
- **Opensearch Dashboard password:** `PimcoreTests1492!`

### Debug API tests
For debugging purposes docker exposes some ports to the host machine. You can access the following services:

- Pimcore Admin UI: `http://localhost:6001/admin`
- Pimcore API: `http://localhost:6002/pimcore-studio/api/docs`
- Pimcore Database: `localhost:6006`
- Opensearch Dashboard: `http://localhost:6004/`

## Run Postman API tests
To run the Postman API tests you need to have Postman installed on your machine. You can download it from [here](https://www.postman.com/downloads/).

1. Open Postman
2. Import all the collections `tests/Api/*.json`
3. Create a new environment with the following variables:
    - `host`: `http://localhost:6001/pimcore-studio/api`
4. Run the collection either by clicking on the `Run` button or by running the following command in the terminal:
```bash
postman login --with-api-key "your_postman_api_key"
postman collection run tests/Api/*.json --environment "UID_of_your_environment"
```


