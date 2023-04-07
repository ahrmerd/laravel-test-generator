<?php

namespace Ahrmerd\TestGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateTestsCommand extends Command
{
    protected $signature = 'generate:tests {model? : Name of the model to generate tests for}
    {--all : Generate tests for all models}
    {--force : Overwrite existing test files}';

    protected $description = 'Generate API tests for all models';

    public function handle()
    {

        $modelName = $this->argument('model');
        $generateAll = $this->option('all');
        $force = $this->option('force');

        // Generate tests for all models if the --all option is passed
        if ($generateAll) {
            $models = $this->getModels();
            foreach ($models as $model) {
                $this->buildTests($model, $force);
            }
            $this->info('All test files generated successfully!');

        } else {
            // Generate tests for a single model if the model name is passed as an argument
            if ($modelName) {
                // $model = app("App\\Models\\$modelName");
                $this->buildTests($modelName, $force);
            } else {
                $this->error('Please provide a model name or use the --all option to generate tests for all models.');
            }
        }

    }

    private function getModels()
    {
        $models = [];

        // Get all PHP files in the app/Models directory
        $files = File::allFiles(app_path('Models'));

        foreach ($files as $file) {
            // Get the fully-qualified class name for the file
            $class = 'App\\Models\\'.pathinfo($file->getPathname(), PATHINFO_FILENAME);

            // Check if the class exists and is an instance of Model
            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[] = class_basename($class);
            }
        }

        return $models;
    }

    protected function buildTests($model, $force = false)
    {
        // Generate test file name
        $testName = Str::studly($model).'Test';

        // Check if test file already exists
        $testPath = base_path('tests/Feature/'.$testName.'.php');

        if (! $force && file_exists($testPath)) {
            $this->error($testName.' already exists. Use --force option to overwrite.');

            return;
        }

        // $controllerNamespace = $this->getNamespace($name);
        $lcModel = Str::lower($model);
        $plrModel = Str::plural($lcModel);
        $replace = [
            '{{ Model }}' => $model,
            '{{Model}}' => $model,
            '{{ plrModel }}' => $plrModel,
            '{{plrModel}}' => $plrModel,
            '{{ lcModel }}' => $lcModel,
            '{{lcModel}}' => $lcModel,
        ];
        $requestRulesReplacements = $this->generateRules($model);
        $replace = array_merge($replace, $requestRulesReplacements);

        $stub = __DIR__.'/stubs/test.stub';
        $testContent = file_get_contents($stub);

        // dump(array_values($replace));

        $testContent = str_replace(array_keys($replace), array_values($replace), $testContent);

        file_put_contents($testPath, $testContent);
        $this->info($testName.' generated successfully.');
    }

    private function generateRules($model)
    {
        $namespace = 'App\Http\Requests';
        $updateFormRequestClass = $namespace.'\\'.'Update'.Str::studly($model).'Request';
        $storeFormRequestClass = $namespace.'\\'.'Store'.Str::studly($model).'Request';

        try {
            $updateFormRules = (new $updateFormRequestClass())->rules();
            $storeFormRules = (new $storeFormRequestClass())->rules();

            // dump($updateFormRequest->rules());
            // dump(implode($updateFormRequest->rules()));

            $updateFormString = implode(', ', array_map(function ($value, $key) {
                return "\"$key\" => \"$value\"";
            }, array_values($updateFormRules), array_keys($updateFormRules)));

            $storeFormString = implode(', ', array_map(function ($value, $key) {
                return "\"$key\" => \"$value\"";
            }, array_values($storeFormRules), array_keys($storeFormRules)));

            // $updateFormRules = '\'' . implode(',' . PHP_EOL, $updateFormRequest->rules()) . '\'';
            // $storeFormRules = implode(',' . PHP_EOL, $storeFormRequest->rules());

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            // or throw a custom exception
            throw new \Exception('Could not extract validation rules from form request classes of'.$model);
        }

        return [
            '{{ storeRules }}' => $storeFormString,
            '{{storeRules}}' => $storeFormString,
            '{{ updateRules }}' => $updateFormString,
            '{{updateRules}}' => $updateFormString,
        ];

    }
}
