# Azure VM deployment

The template creates one Ubuntu 24.04 VM in Central US with a static public IP, Azure DNS label, a VNet/NSG, and a 32 GiB Standard SSD OS disk. The VM runs the Laravel web/API container, MySQL 8.4 and Caddy. MySQL is private to the Compose network. Only ports 80/443 and SSH from the deployer's current IPv4 address are allowed.

Estimated 24/7 list price before tax and data transfer is approximately USD 88.43/month: `Standard_D2s_v3` at USD 0.11/hour, Standard IPv4 at USD 0.005/hour, and one 32 GiB E4 LRS disk at USD 4.48/month. Actual subscription charges and student credit are controlled by Azure.

## Requirements

- Azure CLI logged into the approved subscription, Bicep CLI, Docker Engine, OpenSSL, Python 3, `ssh`, and `scp`.
- An existing SSH key pair. `SSH_PRIVATE_KEY_FILE` may point to a key other than `~/.ssh/rua-azure-ed25519`.
- Outbound internet access from the deploy host and VM. The domain certificate is issued by Let's Encrypt after DNS points to the VM.
- The target resource-group name must not already exist. The script stops rather than reusing or overwriting an existing resource group.

## Deploy

From the repository root:

```bash
DEMO_ACCOUNT_PASSWORD='(enter the agreed demo password)' bash scripts/deploy-azure-vm.sh
```

The script builds the Laravel image locally, creates the Azure VM, transfers the image/configuration, generates random Laravel/MySQL secrets on the deploy host, applies migrations, and runs only `DemoAccountsSeeder`. It does not import the local database or run the general-purpose `DatabaseSeeder`.

The shared demo password is read from the environment or prompted without echo. Never put it in Git, an image, or the server's persistent `.env` file. The temporary seed environment file is removed after seeding. The three accounts are `example@unab.edu.co`, `operador@unab.edu.co`, and `admin@unab.edu.co`.

## Operations

```bash
ssh -i ~/.ssh/rua-azure-ed25519 hcadmin@<VM-public-IP>
sudo docker compose --project-directory /opt/hostalconnect --env-file /opt/hostalconnect/.env -f /opt/hostalconnect/compose.yaml ps
sudo docker compose --project-directory /opt/hostalconnect --env-file /opt/hostalconnect/.env -f /opt/hostalconnect/compose.yaml logs --tail=100
```

The public hostname is `https://<generated-label>.centralus.cloudapp.azure.com`. Check `https://<FQDN>/up` after deployment. The app persists Laravel storage, MySQL data, and Caddy certificates in Docker volumes on the managed OS disk. This single VM is a demo deployment, not high availability; configure and test off-VM database backups before retaining important data.

The current demo is available at `https://hostalconnect-modzr3g6xybig.centralus.cloudapp.azure.com`; its HTTPS health endpoint returned 200, and all three demo-account login/session/logout flows passed. The connected Android has the production-configured debug APK installed and can reach the VM on TCP 443. Chrome Safe Browsing displayed a “Sitio peligroso” warning for this Azure FQDN on the test handset; do not bypass the warning, and review the domain or use a verified custom domain before public distribution. The deployment script is create-only and intentionally refuses to reuse an existing resource group.

To keep charges down after the demo, deallocate the VM in Azure. The hostname will stop serving the app while the VM is deallocated. The managed disk and public IP may continue to incur charges.
