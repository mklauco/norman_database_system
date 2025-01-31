<?php

namespace Database\Seeders\migrators;

use Illuminate\Database\Seeder;
use App\Models\MariaDB\DCTList as OldData;
use App\Models\Empodat\DataCollectionFileUpload;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DCTFIleUploadMigrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $now =  now();
        OldData::orderBy('list_id', 'desc')->chunk(1000, function ($data) use ($now) {
            $p = [];
            foreach ($data as $item) {
                // do something with $item
                $p[] = [
                    'path'                          => $item->list_file,
                    'filename'                      => $item->list_name,
                    'uploaded_at'                   => $item->list_date,
                    'file_hash'                     => $item->list_hash,
                    'database_entity_id'            => 2, // empodat
                    'data_collection_template_id'   => null,
                    'is_public'                     => 1,
                    'created_at'                    => $now,
                    'updated_at'                    => $now,
                ];
            }
            DataCollectionFileUpload::insert($p);
        });
    }
}

// php artisan db:seed --class=Database\Seeders\migrators\DCTFIleUploadMigrator