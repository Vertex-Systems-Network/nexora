# Nexora N0.15 — Data Connections, Premium Selects & Installer Onboarding

N0.15 separates the **primary Nexora relational database** from **auxiliary data services**. Core schema/migrations remain on Laravel-compatible SQL drivers, while document stores, caches and cloud-native services are attached through the Data Connections layer.

## Primary database presets

- MySQL
- MariaDB
- PostgreSQL
- SQLite
- Microsoft SQL Server
- Amazon RDS for MySQL / MariaDB / PostgreSQL / SQL Server
- Amazon Aurora MySQL / PostgreSQL

AWS presets normalize to their compatible Laravel/PDO driver for installation, migrations and runtime configuration. Managed presets never pretend they can create the remote database automatically.

## Auxiliary data connections

The installer can preselect placeholders for:

- MongoDB
- MongoDB Atlas
- Redis
- Amazon DocumentDB
- Amazon ElastiCache for Redis
- Amazon DynamoDB

Credentials are configured later under **Admin → Data Connections** and encrypted at rest. Connector availability is explicit; Nexora can save a planned connection even when the optional runtime adapter is not installed yet.

## Installer UX fixes

- Database status SVG is size-contained and can no longer expand into the card.
- Database and language controls use Nexora premium dropdowns rather than visible native selects.
- Database copy is provider-neutral rather than MySQL-specific.
- Local SVG flag assets are used so Windows browsers do not render regional-indicator letters such as `US` instead of flags.
- The first installer-created Super Admin is explicitly email verified. Login also contains a safe installation-lock recovery for older zero-test states.

## Admin Data Connections

The new Data Connections screen supports create, encrypted credentials, availability state, connection test, audit events, global search integration and removal. Future modules must request connection handles/capabilities instead of reading credentials directly.
