<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Substance field labels
|--------------------------------------------------------------------------
|
| Display labels for `susdat_substances` columns, used so the substance
| page does not print raw column names (issue #43).
|
| Wording follows the legacy NORMAN Substance Database table at
| https://www.norman-network.com/nds/susdat/susdatSearchShow.php.
|
| Be aware that the legacy system is not self-consistent: its factsheet
| calls the same fields CAS Registry Number, InChIKey, DSSTox Substance ID
| and "Monoisotopic mass [g/mol]", where its substance table says CAS RN,
| StdInChIKey, DTXSID and Monoisotopic Mass. Only PubChem CID agrees in
| both. This file follows the substance table.
|
| Columns with no entry here fall back to their raw column name.
|
*/

return [
    'id' => 'Internal ID',
    'code' => 'SusDat Code',
    'prefixed_code' => 'NORMAN SusDat ID',
    'name' => 'Name',
    'name_dashboard' => 'Name - DashBoard',
    'name_chemspider' => 'Name - ChemSpider',
    'name_iupac' => 'Name - IUPAC',
    'cas_number' => 'CAS Number',
    'smiles' => 'SMILES',
    'smiles_dashboard' => 'SMILES - DashBoard',
    'stdinchi' => 'StdInChI',
    'stdinchikey' => 'StdInChIKey',
    'pubchem_cid' => 'PubChem CID',
    'chemspider_id' => 'ChemSpider ID',
    'dtxid' => 'DSSTox Substance ID',
    'molecular_formula' => 'Molecular Formula',
    'mass_iso' => 'Monoisotopic Mass',
    'average_mass' => 'Average Mass',
    'metadata_synonyms' => 'Synonyms',
    'metadata_cas' => 'CAS metadata',
    'metadata_ms_ready' => 'MS-ready metadata',
    'metadata_general' => 'General metadata',
    'categories' => 'Use Categories',
    'sources' => 'Sources',
    'relevant_to_norman' => 'Relevant to NORMAN',
    'added_by' => 'Added by',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
    'deleted_at' => 'Deleted at',
    'canonical_id' => 'Canonical record ID',
    'status' => 'Record status',
    'merged_at' => 'Merged at',
    'merged_by' => 'Merged by',
    'merge_reason' => 'Merge reason',
];
