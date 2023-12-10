<?php
namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class GenerateEntityCommand extends Command
{
    protected $entityName = '';
    protected $migrationFileName = '';
    protected $action = '';
    protected $fields = [];
    protected $signature = 'generate:entity {name}';
    protected $description = 'Generate an entity with the given name';

    public function handle()
    {
        $this->entityName = ucfirst($this->argument('name'));
        $this->migrationFileName = 'create_' . $this->getTableName() . '_table';

        if (!$this->modelExists($this->entityName)) {
            // Le modèle n'existe pas, donc on le crée
            $this->call('make:model', [
                'name' => $this->entityName,
            ]);
            $this->action = "_CREATE";


        } else {
            // Le modèle existe déjà
            $this->info('Model : ' . $this->entityName . ' is already created ');
            $this->action = "_UPDATE";
        }

        // Ask for fields
        while ($field = $this->ask('Enter field name (press Enter to stop)')) {
            $data = $this->askForType();

            if (isset($data['type']) && !isset($data['relationType'])) {
                $this->fields[] = [
                    'field' => $field,
                    'type' => Str::lower($data['type']),
                    'relationType' => null, // Assurez-vous de définir la relation sur null si ce n'est pas un champ de relation
                ];
            } elseif (isset($data['entityRelation']) && isset($data['relationType'])) {
                $this->fields[] = [
                    'field' => $field,
                    'type' => null, // Assurez-vous de définir le type sur null si ce n'est pas un champ de type
                    'entityRelation' => $data['entityRelation'],
                    'relationType' => $data['relationType'],
                ];
            }
        }

        if($this->action === "_CREATE"){
            // Generate migration file
            $this->generateMigrationFile();
        }else if($this->action === "_UPDATE"){
            $this->generateMigrationUpdateFile();
        }

        $this->info('Entity created : ' . $this->entityName);

    }


    protected function askForType()
    {
        $defaultType = 'string'; // Valeur par défaut

        $types = [
            'bigIncrements' => 'Auto-incrementing UNSIGNED BIGINT (primary key)',
            'bigInteger' => 'BIGINT equivalent to the database',
            'binary' => 'BLOB equivalent to the database',
            'boolean' => 'BOOLEAN equivalent to the database',
            'char' => 'CHAR equivalent to the database',
            'date' => 'DATE equivalent to the database',
            'dateTime' => 'DATETIME equivalent to the database',
            'decimal' => 'DECIMAL equivalent with a precision and scale',
            'double' => 'DOUBLE equivalent to the database',
            'enum' => 'ENUM equivalent to the database',
            'float' => 'FLOAT equivalent to the database',
            'geometry' => 'GEOMETRY equivalent to the database',
            'geometryCollection' => 'GEOMETRYCOLLECTION equivalent to the database',
            'increments' => 'Auto-incrementing UNSIGNED INTEGER (primary key)',
            'integer' => 'INTEGER equivalent to the database',
            'ipAddress' => 'IP ADDRESS equivalent to the database',
            'json' => 'JSON equivalent to the database',
            'jsonb' => 'JSONB equivalent to the database',
            'lineString' => 'LINESTRING equivalent to the database',
            'longText' => 'LONGTEXT equivalent to the database',
            'macAddress' => 'MAC ADDRESS equivalent to the database',
            'mediumIncrements' => 'Auto-incrementing UNSIGNED MEDIUMINT (primary key)',
            'mediumInteger' => 'MEDIUMINT equivalent to the database',
            'mediumText' => 'MEDIUMTEXT equivalent to the database',
            'morphs' => 'Adds INTEGER `{$name}_id` and STRING `{$name}_type`',
            'multiLineString' => 'MULTILINESTRING equivalent to the database',
            'multiPoint' => 'MULTIPOINT equivalent to the database',
            'multiPolygon' => 'MULTIPOLYGON equivalent to the database',
            'nullableMorphs' => 'NULLABLEMORPHS equivalent to the database',
            'nullableUuidMorphs' => 'NULLABLEUUIDMORPHS equivalent to the database',
            'nullableTimestamps' => 'Creates `created_at` and `updated_at` columns nullable',
            'point' => 'POINT equivalent to the database',
            'polygon' => 'POLYGON equivalent to the database',
            'rememberToken' => 'Adds `remember_token` as VARCHAR(100)',
            'set' => 'SET equivalent to the database',
            'string' => 'VARCHAR equivalent to the database',
            'smallIncrements' => 'Auto-incrementing UNSIGNED SMALLINT (primary key)',
            'smallInteger' => 'SMALLINT equivalent to the database',
            'softDeletes' => 'Adds `deleted_at` for soft deletes',
            'softDeletesTz' => 'Adds `deleted_at` for soft deletes with timezone',
            'text' => 'TEXT equivalent to the database',
            'time' => 'TIME equivalent to the database',
            'timeTz' => 'TIME equivalent to the database with timezone',
            'timestamp' => 'TIMESTAMP equivalent to the database',
            'timestampTz' => 'TIMESTAMP equivalent to the database with timezone',
            'timestamps' => 'Adds `created_at` and `updated_at` columns',
            'timestampsTz' => 'Adds `created_at` and `updated_at` columns with timezone',
            'tinyIncrements' => 'Auto-incrementing UNSIGNED TINYINT (primary key)',
            'tinyInteger' => 'TINYINT equivalent to the database',
            'unsignedBigInteger' => 'UNSIGNED BIGINT equivalent to the database',
            'unsignedDecimal' => 'UNSIGNED DECIMAL equivalent to the database',
            'unsignedInteger' => 'UNSIGNED INTEGER equivalent to the database',
            'unsignedMediumInteger' => 'UNSIGNED MEDIUMINT equivalent to the database',
            'unsignedSmallInteger' => 'UNSIGNED SMALLINT equivalent to the database',
            'unsignedTinyInteger' => 'UNSIGNED TINYINT equivalent to the database',
            'uuid' => 'UUID equivalent to the database',
            'year' => 'YEAR equivalent to the database',
            'relation' => 'Relation to the database',
        ];

         $type = $this->ask('Select field type [' . $defaultType . '] or type ? for help', $defaultType);

        while (!array_key_exists($type, $types) && $type !== '?') {
            $this->error('Invalid type! Available types: ' . implode(', ', array_keys($types)));
            $type = $this->ask('Select field type or type ? for help', $defaultType);
        }

        if ($type === '?') {
            $this->showAvailableTypes($types);
            $type = $this->ask('Select field type', $defaultType);
        }
        if ($type === 'relation') {
            $relationData = $this->askForRelationType();
        }

        return [
            'type' => $type,
            'entityRelation' => isset($relationData['entityRelation']) ? $relationData['entityRelation'] : null,
            'relationType' => isset($relationData['relationType']) ? $relationData['relationType'] : null,
        ];

    }

    protected function askForRelationType()
    {
        $relationTypes = ['OneToOne', 'OneToMany', 'ManyToOne', 'ManyToMany'];


        $entityRelation = $this->ask('Select entity to relate ');

        $relationType = $this->choice('Select relation type:', $relationTypes);

        return [
            'entityRelation'=>$entityRelation,
            'relationType'=>$relationType
    ];
    }

    protected function showAvailableTypes($types)
    {
        $this->info('Available types with descriptions:');
        foreach ($types as $typeName => $description) {
            $this->line("- $typeName: $description");
        }
    }



    protected function getTableName()
    {
        $tableName = Str::plural(strtolower($this->entityName));
        return $tableName;
    }

    protected function generateMigrationFile()
    {
        $this->call('make:migration', [
            'name' => $this->migrationFileName,
            '--create' => $this->getTableName(),
        ]);

        $migrationFiles = $this->findMigrationFiles($this->migrationFileName);

        if (count($migrationFiles) === 0) {
            $this->error('Migration file not found.');
            return;
        }

        if (count($migrationFiles) > 1) {
            for ($i = 0; $i < count($migrationFiles) - 1; $i++) {
                unlink($migrationFiles[$i]);
            }
        }

        $migrationFile = end($migrationFiles);
        $fileContent = file_get_contents($migrationFile);

        if ($fileContent === false) {
            die("Unable to read migration file.");
        }

        $fieldsToAdd = [];
        $fieldsToRelation = [];
        foreach ($this->fields as $field) {
            $columnName = $field['field'];
            if($field['type']){
                $columnType = $field['type'];
                $fieldsToAdd[] = sprintf("\t\$table->%s('%s');", $columnType, $columnName);
            }
            if($field['relationType']){
                $entityRelation = $field['entityRelation'];
                $relationType = $field['relationType'];
                $fieldsToRelation[] = [
                    'entityRelation' => $entityRelation,
                    'relationType' => $relationType,
                    'field' => $columnName,
                ];
            }
        }

        $tableName = $this->getTableName();

        $newFields =  implode("\n\t\t", $fieldsToAdd);
        // $newFields =  ltrim($newFields, "\t");


        // Recherche et remplacement du contenu de la méthode up()
        $fileContent = $this->replaceUpMethodContent($fileContent, $newFields, $tableName,  $fieldsToRelation);

        $result = file_put_contents($migrationFile, $fileContent);

        // ajout de $fillable
        $fillableFields = array_map(function ($field) {
            return "'$field'";
        }, array_column($this->fields, 'field'));


        $this->updateModelFillable($this->entityName, $fillableFields);
    }
    protected function generateMigrationUpdateFile()
    {


        $migrationFiles = $this->findMigrationFiles($this->migrationFileName);

        if (count($migrationFiles) === 0) {
            $this->call('make:migration', [
            'name' => $this->migrationFileName,
                '--create' => $this->getTableName(),
            ]);
            $migrationFiles = $this->findMigrationFiles($this->migrationFileName);
            // $this->error('Migration file not found.');
            // return;
        }

        if (count($migrationFiles) > 1) {
            for ($i = 0; $i < count($migrationFiles) - 1; $i++) {
                unlink($migrationFiles[$i]);
            }
        }



        $migrationFile = end($migrationFiles);
        $fileContent = file_get_contents($migrationFile);

        if ($fileContent === false) {
            die("Unable to read migration file.");
        }

        $fieldsToAdd = [];
        $fieldsToRelation = [];

        preg_match_all('/\$table->(.*?)\((.*?)\);/', $fileContent, $matches);

        foreach ($matches[1] as $index => $columnType) {
            $columnName = trim($matches[2][$index], "'");
            if($columnType !== "id" && $columnType !== "timestamps"){
                $fieldsToAdd[] = sprintf("\t\$table->%s('%s');", $columnType, $columnName);
            }
        }


        foreach ($this->fields as $field) {
            $columnName = $field['field'];
            if($field['type']){
                $columnType = $field['type'];
                $fieldsToAdd[] = sprintf("\t\$table->%s('%s');", $columnType, $columnName);
            }
            if($field['relationType']){
                $entityRelation = $field['entityRelation'];
                $relationType = $field['relationType'];
                $fieldsToRelation[] = [
                    'entityRelation' => $entityRelation,
                    'relationType' => $relationType,
                    'field' => $columnName,
                ];
            }
        }

        $tableName = $this->getTableName();

        $newFields =  implode("\n\t\t", $fieldsToAdd);
        // $newFields =  ltrim($newFields, "\t");


        // Recherche et remplacement du contenu de la méthode up()
        $fileContent = $this->replaceUpMethodContent($fileContent, $newFields, $tableName,  $fieldsToRelation);

        $result = file_put_contents($migrationFile, $fileContent);

        // ajout de $fillable
        $fillableFields = array_map(function ($field) {
            return "'$field'";
        }, array_column($this->fields, 'field'));


        $this->updateModelFillable($this->entityName, $fillableFields);
    }

    protected function replaceUpMethodContent($fileContent, $newFields, $tableName,  $fieldsToRelation = null)
    {
        // Début de la méthode up()
        $upStart = strpos($fileContent, 'public function up(): void');
        $upStart = strpos($fileContent, '{', $upStart);
        // Fin de la méthode up()
        $upEnd = strpos($fileContent, '}', $upStart);

        // Extraction du contenu de la méthode up()
        $upMethod = substr($fileContent, $upStart + 1, $upEnd - $upStart - 1);

        // Nouveau contenu de la méthode up()
    $newUpMethod = "\n\t\tSchema::create('$tableName', function (Blueprint \$table) {
        \t\$table->id();
        $newFields
        \t\$table->timestamps();
        ";

    // Ajout des relations si nécessaire
    if ($fieldsToRelation && count($fieldsToRelation) > 0) {
        $newUpMethod .= "});\n\n";
        foreach ($fieldsToRelation as $field) {
            $newTableName = Str::plural(strtolower($field['entityRelation']));

            if ($field['relationType'] === "OneToOne") {
                // Gestion OneToOne
                $newUpMethod .= "\t\tSchema::table('$newTableName', function (Blueprint \$table) {
                    \$table->foreignIdFor(\\App\\Models\\{$this->entityName}::class)->constrained()->onDelete('cascade');
                ";
                $methodContent_1 = "\n\t\treturn \$this->belongsTo(\\App\\Models\\{$field['entityRelation']}::class);\n\t";
                $methodContent_2 = "\n\t\treturn \$this->belongsTo(\\App\\Models\\{$this->entityName}::class);\n\t";
                $methodName_1 = Str::singular($newTableName);
                $methodName_2 = Str::singular($tableName);
                $this->updateModelWithMethod($this->entityName, $methodName_1, $methodContent_1);
                $this->updateModelWithMethod($field['entityRelation'], $methodName_2, $methodContent_2);
            } elseif ($field['relationType'] === "OneToMany") {
                // Gestion OneToMany
                $newUpMethod .= "\t\tSchema::table('$newTableName', function (Blueprint \$table) {
                    \$table->foreignIdFor(\\App\\Models\\{$this->entityName}::class)->constrained()->onDelete('cascade');
                \n";
                $methodContent_1 = "\n\t\treturn \$this->hasMany(\\App\\Models\\{$field['entityRelation']}::class);\n\t";
                $methodContent_2 = "\n\t\treturn \$this->belongsTo(\\App\\Models\\{$this->entityName}::class);\n\t";
                $methodName_1 = Str::plural($newTableName);
                $methodName_2 = Str::singular($tableName);
                $this->updateModelWithMethod($this->entityName, $methodName_1, $methodContent_1);
                $this->updateModelWithMethod($field['entityRelation'], $methodName_2, $methodContent_2);

            } elseif ($field['relationType'] === "ManyToOne") {
                // Gestion ManyToOne
                $newUpMethod .= "\t\tSchema::table('$tableName', function (Blueprint \$table) {
                    \$table->foreignIdFor(\\App\\Models\\{$field['entityRelation']}::class)->constrained()->onDelete('cascade');
                ";
                $methodContent_1 = "\n\t\treturn \$this->belongsTo(\\App\\Models\\{$field['entityRelation']}::class);\n\t";
                $methodContent_2 = "\n\t\treturn \$this->hasMany(\\App\\Models\\{$this->entityName}::class);\n\t";
                $methodName_1 = Str::singular($newTableName);
                $methodName_2 = Str::plural($tableName);
                $this->updateModelWithMethod($this->entityName, $methodName_1, $methodContent_1);
                $this->updateModelWithMethod($field['entityRelation'], $methodName_2, $methodContent_2);

            } elseif ($field['relationType'] === "ManyToMany") {
                // Gestion ManyToMany
                $tab = collect([
                    Str::singular($tableName),
                    Str::singular($newTableName),
                ])->sort()->implode('_');
                $newUpMethod .= "\t\tSchema::create('" . $tab . "', function (Blueprint \$table) {
                    \$table->foreignIdFor(\\App\\Models\\{$this->entityName}::class)->constrained()->onDelete('cascade');
                    \$table->foreignIdFor(\\App\\Models\\{$field['entityRelation']}::class)->constrained()->onDelete('cascade');
                    \$table->primary(['".Str::singular(strtolower($tableName))."_id','".Str::singular(strtolower($field['entityRelation']))."_id']);
                ";
                $methodContent_1 = "\n\t\treturn \$this->belongsToMany(\\App\\Models\\{$field['entityRelation']}::class);\n\t";
                $methodContent_2 = "\n\t\treturn \$this->belongsToMany(\\App\\Models\\{$this->entityName}::class);\n\t";
                $methodName_1 = Str::plural($newTableName);
                $methodName_2 = Str::plural($tableName);
                $this->updateModelWithMethod($this->entityName, $methodName_1, $methodContent_1);
                $this->updateModelWithMethod($field['entityRelation'], $methodName_2, $methodContent_2);
            }
        }
    }

        $fileContent = substr_replace($fileContent, $newUpMethod, $upStart + 1, $upEnd - $upStart - 1);
        return $fileContent;
    }

    protected function modelExists($modelName)
    {
        $modelFile = app_path('Models/' . $modelName . '.php');
        return File::exists($modelFile);
    }


    protected function findMigrationFiles($migrationFileName)
    {
        return glob(database_path('migrations') . '/*_' . $migrationFileName . '.php');
    }

    protected function findModelFile($modelName)
    {
        $modelFilePath = app_path('Models/' . $modelName . '.php');

        if (file_exists($modelFilePath)) {
            return $modelFilePath;
        }

        return null;
    }
    protected function findModelRelativeFile($modelName)
    {
        $rootPath = base_path();
        $modelFilePath = '/app/Models/' . $modelName . '.php';

        if (file_exists($modelFilePath)) {
            return $modelFilePath;
        }

        return null;
    }

    // Ajoutez cette méthode dans votre classe GenerateEntityCommand
    protected function updateModelFillable($modelName, $fillableArray)
    {
        $modelFile = $this->findModelFile($modelName);

        if ($modelFile) {
            // Lire le contenu du fichier du modèle
            $fileContent = file_get_contents($modelFile);

            // Recherche du tableau fillable
            $fillablePosition = strpos($fileContent, 'protected $fillable');

            if ($fillablePosition !== false) {
                // Si le tableau fillable est trouvé, mettre à jour son contenu
                $fillableStart = strpos($fileContent, '[', $fillablePosition);
                $fillableEnd = strpos($fileContent, ']', $fillableStart);


                // Extraire le contenu du tableau fillable et le convertir en tableau
                $existingFillableString = substr($fileContent, $fillableStart + 1, $fillableEnd - $fillableStart - 1);
                $existingFillable = explode(', ', $existingFillableString);

                // Mettre à jour le contenu du tableau fillable avec les nouveaux champs
                $newFillable = array_merge($existingFillable, $fillableArray);
                $newFillableContent = '[' . implode(', ', $newFillable) . ']';

                // Remplacer le contenu du tableau fillable existant par le nouveau contenu
                $fileContent = substr_replace($fileContent, $newFillableContent, $fillableStart, $fillableEnd - $fillableStart + 1);

                // Écrire le contenu modifié dans le fichier
                file_put_contents($modelFile, $fileContent);

                $this->info("Fillable fields updated in $modelName model.");
            } else {
                // Si le tableau fillable n'est pas trouvé, ajouter le tableau fillable au modèle

                // Trouver la position de la dernière accolade fermante dans le fichier
                $lastBracePosition = strrpos($fileContent, '}');

                // Construire le contenu du nouveau tableau fillable
                $newFillableContent = "\n\tprotected \$fillable = [" . implode(', ', $fillableArray) . "];\n}";

                // Insérer le contenu du nouveau tableau fillable avant la dernière accolade fermante
                $fileContent = substr_replace($fileContent, $newFillableContent, $lastBracePosition, 1);

                // Écrire le contenu modifié dans le fichier
                file_put_contents($modelFile, $fileContent);

                $this->info("Fillable fields added to $modelName model.");
            }
        } else {
            $this->error("Model file not found for $modelName");
        }
    }


    protected function updateModel($modelName)
    {
        $modelFile = $this->findModelFile($modelName);

        if ($modelFile) {
            // Lire le contenu du fichier du modèle
            $fileContent = file_get_contents($modelFile);

            // Vérifier si la méthode up() existe déjà dans le fichier
            $methodPosition = strpos($fileContent, 'public function up()');

            if ($methodPosition !== false) {
                // Si la méthode existe déjà, utiliser la logique de modification ici...
                // (voir l'exemple précédent)

                $this->info('Method up() in ' . $modelName . ' model updated.');
            } else {
                // Si la méthode n'existe pas, ajouter la méthode up() au modèle

                // Trouver la position de la dernière accolade fermante dans le fichier
                $lastBracePosition = strrpos($fileContent, '}');

                // Construire le contenu de la méthode up()
                $newUpMethodContent = "\n\tpublic function up()\n\t{\n\t\t// Contenu de la méthode up()\n\t}\n\n";

                // Insérer le contenu de la nouvelle méthode up() avant la dernière accolade fermante
                $fileContent = substr_replace($fileContent, $newUpMethodContent, $lastBracePosition, 0);

                // Écrire le contenu modifié dans le fichier
                file_put_contents($modelFile, $fileContent);

                $this->info('Method up() created in ' . $modelName . ' model.');
            }
        } else {
            $this->error('Model file not found for ' . $modelName);
        }
    }

    protected function updateModelWithMethod($modelName, $methodName, $methodContent)
    {
        $modelFile = $this->findModelFile($modelName);

        if ($modelFile) {
            // Lire le contenu du fichier du modèle
            $fileContent = file_get_contents($modelFile);

            // Vérifier si la méthode existe déjà dans le fichier
            $methodPosition = strpos($fileContent, "public function $methodName()");

            if ($methodPosition !== false) {
                // Si la méthode existe déjà, modifier son contenu
                // Récupérer le début et la fin de la méthode
                $methodStart = strpos($fileContent, '{', $methodPosition);
                $methodEnd = strpos($fileContent, '}', $methodStart);

                // Extraire le contenu de la méthode
                $existingMethod = substr($fileContent, $methodStart + 1, $methodEnd - $methodStart - 1);

                // Remplacer le contenu existant par le nouveau contenu de la méthode
                $fileContent = str_replace($existingMethod, $methodContent, $fileContent);

                file_put_contents($modelFile, $fileContent);

                // $this->info("Method $methodName() in $modelName model updated.");
            } else {
                // Si la méthode n'existe pas, ajouter la méthode au modèle

                // Trouver la position de la dernière accolade fermante dans le fichier
                $lastBracePosition = strrpos($fileContent, '}');

                // Construire le contenu de la nouvelle méthode
                $newMethodContent = "\n\tpublic function $methodName()\n\t{\n\t\t$methodContent\n\t}\n\n";

                // Insérer le contenu de la nouvelle méthode avant la dernière accolade fermante
                $fileContent = substr_replace($fileContent, $newMethodContent, $lastBracePosition, 0);

                // Écrire le contenu modifié dans le fichier
                file_put_contents($modelFile, $fileContent);

                // $this->info("Method $methodName() created in $modelName model.");
            }

            $m = $this->findModelRelativeFile($modelName);

            $this->info("Model $m is updated.");
        } else {
            $this->error("Model file not found for $modelName");
        }
    }

}

?>
