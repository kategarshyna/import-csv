# Code Samples (import-csv) Repository

This repository contains code samples that demonstrate:

1. Migrations for creating `EClassTree` and `translations` tables.
2. A PHP console command for importing data into the new tables from *.csv files.
3. An API endpoint to retrieve the E-Class tree.

The code samples adhere to the Symfony `5.2` structure.

### Command "app:import:eclass:tree"
#### Options
Option | Description                                                    | Default
--- |----------------------------------------------------------------| ---
version | E-Class version of imported file                               | '12.0'
customize-only | Run only customization file without main E-Classes import file | false
locale | Locale of E-Class to import                                    | null
