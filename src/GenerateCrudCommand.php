<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class GenerateCrudCommand extends Command
{
    protected $entity = '';

    protected $signature = 'generate:crud {entity}';

    protected $description = 'Generate CRUD for specified entity';

    public function handle()
    {
        $this->entity = Str::lower($this->argument('entity'));

        // Logique pour générer les fichiers du CRUD ici
        // Par exemple, génération de contrôleurs, de routes, de vues, etc.
        // Utilisez les outils de Laravel comme Artisan::call() pour générer des resources.

        $this->createController();
        $this->createViews();
        $this->createRoutes();

        $this->info("CRUD generated successfully for {$this->entity}.");
    }

    public function createController(){

        Artisan::call('make:request ' . ucfirst($this->entity) . 'FormRequest');
        Artisan::call('make:controller ' . ucfirst($this->entity) . 'Controller --resource');
        $EntityName = ucfirst($this->entity);
        $entityName = lcfirst($this->entity);
        $entityNames = Str::plural($this->entity);

        $contentController = <<<EOD
<?php

namespace App\Http\Controllers;

use App\Models\\$EntityName;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\\{$EntityName}FormRequest;
use Illuminate\Support\Facades\Storage;

class {$EntityName}Controller extends Controller
{
    public function index(): View
    {
        \$$entityNames = $EntityName::orderBy('created_at', 'desc')->paginate(5);
        return view('{$entityName}/index', ['$entityNames' => \$$entityNames]);
    }

    public function show(\$id): View
    {
        \$$entityName = $EntityName::findOrFail(\$id);

        return view('{$entityName}/show',['$entityName' => \$$entityName]);
    }
    public function create(): View
    {
        return view('{$entityName}/create');
    }

    public function edit(\$id): View
    {
        \$$entityName = $EntityName::findOrFail(\$id);
        return view('{$entityName}/edit', ['$entityName' => \$$entityName]);
    }

    public function store({$EntityName}FormRequest \$req): RedirectResponse
    {
        \$data = \$req->validated();
        \$$entityName = $EntityName::create(\$data);
        return redirect()->route('admin.{$entityName}.show', ['id' => \${$entityName}->id]);
    }

    public function update($EntityName \$$entityName, {$EntityName}FormRequest \$req)
    {
        \$data = \$req->validated();
        \${$entityName}->update(\$data);

        return redirect()->route('admin.{$entityName}.show', ['id' => \${$entityName}->id]);
    }

    public function delete($EntityName \$$entityName)
    {
        \${$entityName}->delete();

        return [
            'isSuccess' => true
        ];
    }
}
EOD;

$rules = '';
$fields = $this->getFields();
$count = count($fields);

foreach ($fields as $index => $field) {
    if ($index === $count - 1) {
        $rules .= "'$field' => 'required'\n\t\t\t";
    } else {
        $rules .= "'$field' => 'required',\n\t\t\t";
    }
}

$contentRequests = <<<EOD
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$EntityName}FormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            $rules
        ];
    }
    public function prepareForValidation()
    {
        \$this->merge([]);
    }
}
EOD;




                file_put_contents(app_path('Http/Controllers/' . $EntityName . 'Controller.php'), $contentController);
                file_put_contents(app_path('Http/Requests/' . $EntityName . 'FormRequest.php'), $contentRequests);


    }
    public function createViews()
    {
        $directory = resource_path('views/' . $this->entity);

        // Vérifier si le dossier existe déjà
        if (!File::isDirectory($directory)) {
            // Si le dossier n'existe pas, le créer avec les permissions
            File::makeDirectory($directory, 0755, true);
        }

        $this->createViewForm();
        $this->createViewIndex();
        $this->createViewCreate();
        $this->createViewEdit();
        $this->createViewShow();
    }



    protected function createViewShow()
    {
        $content = '<div class="data-line">';
        $entityName = ucfirst($this->entity);
        $entityInstance = Str::camel($this->entity); // Instance de l'entité
        foreach ($this->getFields() as $field) {
            if ($content !== '') {
                $content .= "\n";
            }
            $content .= <<<HTML
                    <div class="name"><strong>$field</strong> : {{ \$$entityInstance->$field }}</div>
            HTML;
        }
        $content .= "\n\t</div>";

        $viewContent = <<<EOD
            @extends('base')

            @section('content')
                <h1>Show $entityName</h1>
                <a href="{{ route('admin.{$entityInstance}.index') }}\" class=\"btn btn-success btn-sm\">
                    <button class="btn btn-success">
                        Home
                    </button>
                </a>
                $content
                <a href="{{ route('admin.{$entityInstance}.edit', ['id' => \${$entityInstance}->id]) }}" class=\"btn btn-warning btn-sm\">
                    <button class="btn btn-success">
                        Edit
                    </button>
                </a>
            @endsection
            EOD;

        File::put(resource_path('views/' . $this->entity . '/show.blade.php'), $viewContent);
        $this->info('Create : resources/views/' . $this->entity . '/show.blade.php');
    }

    protected function createViewCreate()
    {
        $entityName = ucfirst($this->entity);
        $entityInstance = Str::camel($this->entity); // Instance de l'entité


        $viewContent = <<<EOD
        @extends('base')

        @section('content')
            <h1>Create {$entityName}</h1>
            <a href="{{ route('admin.{$entityInstance}.index') }}" class="btn btn-success btn-sm">
                <button class="btn btn-success">
                    Home
                </button>
            </a>
            @include('{$entityInstance}/{$entityInstance}Form')
        @endsection
        @section('scripts')
            <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
            <script>
                ClassicEditor
                    .create( document.querySelector( '#content' ) )
                    .catch( error => {
                        console.error( error );
                    } );
                $(document).ready(function() {
                    $('select').select2();
                });
            </script>
        @endsection
        EOD;

        File::put(resource_path('views/' . $this->entity . '/create.blade.php'), $viewContent);
        $this->info('Created: resources/views/' . $this->entity . '/create.blade.php');
    }
    protected function createViewEdit()
    {
        $entityName = ucfirst($this->entity);
        $entityInstance = Str::camel($this->entity); // Instance de l'entité


        $viewContent = <<<EOD
        @extends('base')

        @section('content')
            <h1>Edit {$entityName}</h1>
            <a href="{{ route('admin.{$entityInstance}.index') }}" class="btn btn-success btn-sm">
            <button class="btn btn-success">
                Home
            </button>
        </a>
            @include('{$entityInstance}/{$entityInstance}Form', ['$entityInstance' => \$$entityInstance])
        @endsection
        @section('scripts')
            <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
            <script>
                ClassicEditor
                    .create( document.querySelector( '#content' ) )
                    .catch( error => {
                        console.error( error );
                    } );
                $(document).ready(function() {
                    $('select').select2();
                });
            </script>
        @endsection
        EOD;

        File::put(resource_path('views/' . $this->entity . '/edit.blade.php'), $viewContent);
        $this->info('Create : resources/views/' . $this->entity . '/edit.blade.php');
    }
    protected function createViewForm()
    {
        $entityName = ucfirst($this->entity);
        $entityInstance = Str::camel($this->entity); // Instance de l'entité

        $formAction = "{{ isset(\${$entityInstance}) ? route('admin.{$entityInstance}.update', ['{$entityInstance}' => \${$entityInstance}->id]) : route('admin.{$entityInstance}.store') }}";

        $content = <<<HTML
            <form action="{$formAction}" method="POST">
                @csrf
        HTML;

        foreach ($this->getFields() as $field) {
            $content .= "\n\t\t<input type=\"text\" class=\"form-control\" name=\"{$field}\" placeholder=\"{$field} ...\" id=\"{$field}\" value=\"{{ old('{$field}', isset(\${$entityInstance}) ? \${$entityInstance}->{$field} : '') }}\">
                @error('$field')
                        <div class=\"error\">
                            {{ \$message }}
                        </div>
                @enderror
            ";
        }

        $content .= "\n\t\t<button class=\"btn btn-primary mt-1\"> {{ isset(\${$entityInstance}) ? 'Update' : 'Create' }}</button>";
        $content .= "\n\t</form>";

        $viewContent = <<<EOD
            <h2>{$entityName} Form</h2>
            {$content}
        EOD;

        File::put(resource_path('views/' . $this->entity .'/'. $this->entity.'Form.blade.php'), $viewContent);
        $this->info('Create : resources/views/' . $this->entity .'/'. $this->entity.'Form.blade.php');
    }

    protected function createViewIndex()
    {

            $thead = '';
            $entityName = ucfirst($this->entity);
            $entityNames = Str::plural($this->entity);
            $entityInstance = Str::camel($this->entity); // Instance de l'entité

            foreach ($this->getFields() as $field) {
                if($thead !== '')
                    $thead .= "\n\t\t\t\t\t\t";
                $thead .= "<th scope=\"col\">$field</th>";
            }
            $thead .= "\n\t\t\t\t\t\t<th scope=\"col\">Actions</th>";

            $tbody = "@foreach(\$$entityNames as \${$this->entity})\n\t\t\t\t\t\t";
            $tbody .= "<tr>";
                foreach ($this->getFields() as $field) {
                    $tbody .= "\n\t\t\t\t\t\t\t";
                    $tbody .= "<td>{{ \${$this->entity}->$field }}</td>";
                }
                $tbody .= "\n\t\t\t\t\t\t<td>
                <a href=\"{{ route('admin.{$entityInstance}.show', ['id' => \${$entityInstance}->id]) }}\" class=\"btn btn-primary btn-sm\">
                    <i class=\"fa-solid fa-eye\"></i>
                </a>
                <a href=\"{{ route('admin.{$entityInstance}.edit', ['id' => \${$entityInstance}->id]) }}\" class=\"btn btn-success btn-sm\">
                    <i class=\"fa-solid fa-pen-to-square\"></i>
                </a>
                <a href=\"#\" data-id=\"{{ \${$entityInstance}->id }}\" class=\"btn btn-danger btn-sm deleteBtn\">
                    <i class=\"fa-solid fa-trash\"></i>
                </a>
            </td>\n\t\t\t\t\t\t";


                $tbody .= "<tr>\n\t\t\t\t\t";
            $tbody .= "@endforeach";


            // Générer la vue Show avec un tableau Bootstrap et un entête dynamique
            $content = <<<EOD
                        @extends('base')

                        @section('styles')
                            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
                        @endsection

                        @section('content')
                            <h1> $entityName Details</h1>

                            <a href="{{ route('admin.{$entityInstance}.create') }}\" class=\"btn btn-success btn-sm\">
                                <button class="btn btn-success">
                                    Create {$entityName}
                                </button>
                            </a>

                            <div class="card">
                                <div class="card-body">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                $thead
                                            </tr>
                                        </thead>
                                        <tbody>
                                            $tbody
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div class="d-flex justify-content-center">
                                        {{ \${$entityNames}->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                    <h1 class="modal-title fs-5" id="confirmModalLabel">Delete confirm</h1>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                    ...
                                    </div>
                                    <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary confirmDeleteAction">Delete</button>
                                    </div>
                                </div>
                                </div>
                            </div>
                        @endsection
                        @section('scripts')
                            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
                            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js" integrity="sha384-cuYeSxntonz0PPNlHhBs68uyIAVpIIOZZ5JqeqvYYIcEL727kskC66kF92t6Xl2V" crossorigin="anonymous"></script>
                            <script>
                                const deleteButtons = document.querySelectorAll('.deleteBtn')
                                deleteButtons.forEach(deleteButton => {
                                    deleteButton.addEventListener('click', (event)=>{
                                        event.preventDefault();
                                        const { id , title } = deleteButton.dataset
                                        const modalBody = document.querySelector('.modal-body')
                                        modalBody.innerHTML = `Are you sure you want to delete this data ?</strong> `
                                        console.log({ id , title });
                                        const modal = new bootstrap.Modal(document.querySelector('#confirmModal'))
                                        modal.show()
                                        const confirmDeleteBtn = document.querySelector('.confirmDeleteAction')

                                        confirmDeleteBtn.addEventListener('click',async ()=>{
                                            const csrfToken = document.head.querySelector('meta[name="csrf-token"]').content;
                                            const response = await fetch('/admin/{$entityInstance}/delete/'+id , {
                                                method: 'DELETE',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': csrfToken
                                                }
                                            })

                                            const result = await response.json()

                                            if(result && result.isSuccess){
                                                window.location.href = window.location.href;
                                            }


                                            modal.hide()
                                        })
                                    })

                                });
                            </script>
                        @endsection
                        EOD;

            File::put(resource_path('views/' . $this->entity . '/index.blade.php'), $content);
            $this->info('Create : resources/views/' . $this->entity . '/index.blade.php');
    }

    protected function createRoutes()
    {
        $entityName = ucfirst($this->entity);
        $entityInstance = Str::camel($this->entity);
        $controllerNamespace = 'App\\Http\\Controllers\\'; // Ajoutez votre namespace ici si différent

        $routeContent = <<<EOD
        Route::prefix('admin')->group(function(){
            Route::get('/{$entityInstance}', '{$controllerNamespace}{$entityName}Controller@index')->name('admin.{$entityInstance}.index');
            Route::get('/{$entityInstance}/show/{id}', '{$controllerNamespace}{$entityName}Controller@show')->name('admin.{$entityInstance}.show');
            Route::get('/{$entityInstance}/create', '{$controllerNamespace}{$entityName}Controller@create')->name('admin.{$entityInstance}.create');
            Route::get('/{$entityInstance}/edit/{id}', '{$controllerNamespace}{$entityName}Controller@edit')->name('admin.{$entityInstance}.edit');
            Route::post('/{$entityInstance}/store', '{$controllerNamespace}{$entityName}Controller@store')->name('admin.{$entityInstance}.store');
            Route::post('/{$entityInstance}/update/{{$entityInstance}}', '{$controllerNamespace}{$entityName}Controller@update')->name('admin.{$entityInstance}.update');
            Route::delete('/{$entityInstance}/delete/{{$entityInstance}}', '{$controllerNamespace}{$entityName}Controller@delete')->name('admin.{$entityInstance}.delete');
        });
        EOD;

        File::append(base_path('routes/web.php'), $routeContent);
        $this->info('Update : routes/web.php');
    }

    protected function getFields(){
        $migrationFileName = database_path('migrations') . "/" . $this->getMigrationFileName();

        $fields = [];
        if (file_exists($migrationFileName)) {
            // Extraire les champs de la migration
            $migrationContent = file_get_contents($migrationFileName);
            preg_match_all('/\$table->([\w]+)\(([^)]+)/', $migrationContent, $matches);

            foreach ($matches[2] as $match) {
                $fieldData = explode(',', $match);
                $fieldName = trim($fieldData[0], "'\"");

                // Vérifier si le champ appartient au modèle App\Models
                if (
                    strpos($fieldName, '\\App\\Models\\') === false &&
                    strpos($fieldName, '[') === false
                ) {
                    $fields[] = $fieldName;
                }
            }
        }
        return $fields;
    }
    protected function getMigrationFileName()
    {
        $migrationsPath = database_path('migrations');
        $entityMigration = '';

        $files = scandir($migrationsPath);

        foreach ($files as $file) {
            if (strpos($file, '_create_' . Str::snake(Str::plural($this->entity)) . '_table.php') !== false) {
                $entityMigration = $file;
                break;
            }
        }

        return $entityMigration;
    }


}
