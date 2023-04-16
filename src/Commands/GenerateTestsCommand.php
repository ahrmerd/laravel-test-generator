<?php

namespace Ahrmerd\TestGenerator\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Summary of GenerateTestsCommand
 */
class GenerateTestsCommand extends Command
{
    protected $signature = 'generate:tests {model? : Name of the model to generate tests for}
    {--all : Generate tests for all models}
    {--force : Overwrite existing test files}
    {--api : Generate API tests}
    {--web : Generate web tests}
    ';

    protected $description =
        'Generates comprehensive API and web tests for Laravel Eloquent models. Speed up your testing workflow with just a few simple commands!';
    /*
    |--------------------------------------------------------------------------
    | Package Features
    |--------------------------------------------------------------------------
    | - Generates tests for Laravel Eloquent models
    | - Covers both API and web testing
    | - Provides comprehensive test coverage
    | - Simplifies testing workflow with easy-to-use commands
    |
    | Additional Notes:
    | - Utilizes Ahrmerd/TestGenerator package for test generation
    | - Helps developers ensure code quality and reliability through testing
    | - Saves time and effort in writing repetitive test code
    | - Improves overall software quality by automating testing processes
    | - Boosts development productivity by providing a faster way to create tests
    | - Streamlines the testing workflow and enhances the development cycle
    | - Enables developers to quickly validate model functionality and behavior
    | - Provides an efficient way to catch bugs and prevent regressions in code
    | Usage:
    | - Run "php artisan test:generate {model}" command to generate tests for a model
    | - Customize generated tests to suit specific testing requirements
    | - Automate testing of Laravel applications with confidence and ease
    |--------------------------------------------------------------------------
    */

    /**
     * Handle the command.
     *
     * Executes the command logic based on the provided arguments and options.
     *
     * @return void
     */
    public function handle()
    {
        // Get the provided arguments and options
        $modelName = $this->argument('model');
        $generateAll = $this->option('all');
        $force = $this->option('force');

        // Generate tests for all models if the --all option is passed
        if ($generateAll) {
            // Get the list of all model classes and generate tests for each one
            $models = $this->getModels();
            foreach ($models as $model) {
                // Call the buildTests method with the provided model name and force option
                $this->buildTests($model, $force);
            }
            $this->info('All test files generated successfully!');

        } else {
            // Generate tests for a single model if the model name is passed as an argument
            if ($modelName) {
                // Call the buildTests method with the provided model name and force option
                $this->buildTests($modelName, $force);
            } else {
                $this->error('Please provide a model name or use the --all option to generate tests for all models.');
            }
        }

    }

    /**
     * Get the list of model classes in the app/Models directory.
     *
     * @return array<string> array An array of model class names.
     */
    private function getModels()
    {
        //retrieves the models to ignore from the config file
        $ignoreModels = config('TestGenerator.ignore_models', []);
        $models = [];

        // Get all PHP files in the app/Models directory
        $files = File::allFiles(app_path('Models'));

        foreach ($files as $file) {
            // Get the fully-qualified class name for the file
            $class = 'App\\Models\\'.pathinfo($file->getPathname(), PATHINFO_FILENAME);

            // Check if the class exists and is an instance of Model and adds to the $models array
            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $models[] = class_basename($class);
            }
        }
        $models = array_diff($models, $ignoreModels);

        return $models;
    }

    /**
     * Build tests for a given model.
     *
     * Generates a test file for the specified model, including the necessary
     * replacements in the stub file.
     *
     * @param  string  $model The name of the model for which tests are to be generated
     * @param  bool  $force Whether to force overwrite if the test file already exists
     * @return void
     */
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

        // Generate replacements for stub file
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

        // Generate replacements for request rules in stub file
        $requestRulesReplacements = $this->generateRules($model);
        $replace = array_merge($replace, $requestRulesReplacements);

        // Load stub file and perform replacements
        $stub = $this->getStub();
        $testContent = str_replace(array_keys($replace), array_values($replace), $stub);

        // Write test file to disk
        file_put_contents($testPath, $testContent);
        $this->info($testName.' generated successfully.');
    }

    //the paths for the stub files
    private $bothStubPath = __DIR__.'/stubs/both_test.stub';

    private $apiStubPath = __DIR__.'/stubs/api_test.stub';

    private $webStubPath = __DIR__.'/stubs/web_test.stub';

    /**
     * Get the stub file content based on the options passed.
     *
     * @return string The content of the stub file
     */
    private function getStub()
    {
        $generateApi = $this->option('api'); // Check if --api option is passed
        $generateWeb = $this->option('web'); // Check if --web option is passed

        $stubPath = $this->getDefaultStubPath(); // Set the default stub file path from the config

        if ($generateApi && $generateWeb) {
            $stubPath = $this->bothStubPath;
        } elseif ($generateApi) {
            $stubPath = $this->apiStubPath;
        } elseif ($generateWeb) {
            $stubPath = $this->webStubPath;
        }

        return file_get_contents($stubPath); // Return the content of the selected stub file
    }

    /**
     * gets a stub path based on the default option set on the config file
     *
     * @return string: the default stub path based on the config
     */
    private function getDefaultStubPath()
    {
        $default = config('TestGenerator.default', 'both');

        if ($default == 'api') {
            return $this->apiStubPath;
        } elseif ($default == 'web') {
            return $this->webStubPath;
        } else {
            return $this->bothStubPath;
        }

    }

    /**
     * Generates validation rules for the specified model by instantiating the
     * corresponding form request classes and extracting the rules from them.
     *
     * @param  string  $model The name of the model for which validation rules are to be generated
     * @return array An array of validation rules, with keys for storeRules and updateRules
     *
     * @throws \Exception If validation rules cannot be extracted from form request classes
     */
    private function generateRules($model)
    {
        // Set the namespace for form request classes
        $namespace = 'App\Http\Requests';

        // Build the class names for update and store form request classes
        $updateFormRequestClass = $namespace.'\\'.'Update'.Str::studly($model).'Request';
        $storeFormRequestClass = $namespace.'\\'.'Store'.Str::studly($model).'Request';

        try {
            if (class_exists($updateFormRequestClass) && class_exists($storeFormRequestClass)) {
                // Instantiate form request classes and extract rules
                $updateFormRules = (new $updateFormRequestClass())->rules();
                $storeFormRules = (new $storeFormRequestClass())->rules();

                // Convert rules arrays to string representation
                $updateFormString = implode(', ', array_map(function ($value, $key) {
                    return "\"$key\" => \"$value\"";
                }, array_values($updateFormRules), array_keys($updateFormRules)));

                $storeFormString = implode(', ', array_map(function ($value, $key) {
                    return "\"$key\" => \"$value\"";
                }, array_values($storeFormRules), array_keys($storeFormRules)));
            } else {
                throw new Exception('Could not extract validation rules from form request classes of '.$model);
            }

        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw new Exception('Could not extract validation rules from form request classes of '.$model);
        }

        // Return the rules as replacements for stub file
        return [
            '{{ storeRules }}' => $storeFormString,
            '{{storeRules}}' => $storeFormString,
            '{{ updateRules }}' => $updateFormString,
            '{{updateRules}}' => $updateFormString,
        ];

    }
}
