# Api Testing

## Set up local test environment
We provide a shell script to set up a local test environment. This script will install a fresh Pimcore instance and install the Studio Backend Bundle with all this dependencies. The script is located in the `tests/Api` directory.

```bash
cd tests/Api
sudo chmod +x boot-test-env.sh
./boot-test-env.sh
```


## Credentials

- **Pimcore admin user:** `admin`
- **Pimcore admin password:** `admin`
- **Database user:** `pimcore`
- **Database password:** `ROOT`
- **Database name:** `pimcore`
- **Opensearch Dashboard user:** `admin`
- **Opensearch Dashboard password:** `PimcoreTests1492!`

# Debug API tests
For debuging purposes docker exposes some ports to the host machine. You can access the following services:

- Pimcore Admin UI: `http://localhost:6001/admin`
- Pimcore API: `http://localhost:6002/studio/api/docs`
- Pimcore Database: `localhost:6006`
- Opensearch Dashboard: `http://localhost:6004/`



