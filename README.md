External Data Issues Handling
Because the data source is external, several potential issues must be considered. Below are the main risks and how the current implementation addresses them (or how they could be improved with more time):

1. Whether the data is correctly formatted for CSV
   Risk: Missing headers, extra/missing columns, invalid delimiters, or empty lines can break the import process.

Current solution:

The importer uses league/csv library which automatically handles different line endings and encodings.

It skips empty lines automatically and maps rows to headers, filling missing fields with empty strings.

Rows with invalid structure or parse errors cause exceptions which are caught and logged.

Future improvements:

Implement stricter validation on row completeness and field formats in the ProductValidator.

2. Whether the data is correctly formatted for use with a database
   Risk: Strings that are too long, invalid data types (e.g., text in numeric columns), or values that violate database constraints can cause import failures.

Current solution:

Dedicated ProductValidator checks field types and required values before insertion.

Rows failing validation are skipped and logged.

Future improvements:

Extend validation rules for field lengths, formats, and constraints using Symfony Validator or custom logic.

3. Potential data encoding issues or line termination problems
   Risk: CSV files may use different encodings (UTF-8, ISO-8859-1, etc.) or line endings (Windows \r\n, Unix \n, Mac \r), which can cause parsing errors.

Current solution:

league/csv normalizes line endings and supports various encodings, minimizing related issues.

Future improvements:

Detect and convert file encodings to UTF-8 on import for consistent processing.

4. Manual interference with the file which may invalidate some entries
   Risk: Human modifications (e.g., accidental deletions, formatting in Excel) can lead to corrupted data.

Current solution:

Invalid or missing mandatory fields cause row skipping with detailed error logging.

Duplicate product codes are handled with ON DUPLICATE KEY UPDATE to keep data consistent.

Future improvements:

Pre-import checksum validation or file audit mode to detect manual tampering.

Recommendation on Database Encoding
If your database tables use the latin1 encoding, special characters in text fields (like curly quotes “ ”) may be saved incorrectly and appear as garbled symbols (e.g., â€). To avoid this, it's best to convert your tables and test database to utf8mb4 encoding. This ensures all characters are stored and displayed properly.

Also, make sure your database connection is configured to use UTF-8 encoding, so your application and database communicate using the same character set and data integrity is preserved

Migration:

php bin/console doctrine:migrations:migrate

Usage

Run the import command:

php bin/console app:import-products [filename] [directory] [--test]

Example:

php bin/console app:import-products products.csv

php bin/console app:import-products products.csv /tmp/import --test

Error Logging

Rows that fail validation or database insertion are skipped and logged to:

var/log/import_errors_[date].log