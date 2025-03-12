<?php

namespace WizardingCode\RedisStream\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeProducerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis-stream:make-producer 
                            {name : The name of the producer class}
                            {--stream= : The stream name for this producer}
                            {--path= : The path where the producer class should be created}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Redis Stream producer class';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $stream = $this->option('stream') ?? Str::snake(class_basename($name));
        
        $stub = $this->getStub();
        $className = $this->qualifyClass($name);
        $path = $this->getPath($className);
        
        // Check if class already exists
        if ($this->files->exists($path) && !$this->confirm("The file {$path} already exists. Do you want to overwrite it?")) {
            $this->error('Producer creation cancelled.');
            return 1;
        }
        
        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
        
        // Replace placeholders in stub
        $stub = $this->replaceNamespace($stub, $className)
                     ->replaceClass($stub, $className)
                     ->replaceStream($stub, $stream);
        
        // Write the file
        $this->files->put($path, $stub);
        
        $this->info("Producer created successfully: {$className}");
        
        return 0;
    }
    
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->files->get(__DIR__ . '/../../stubs/producer.stub');
    }
    
    /**
     * Get the fully qualified class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass(string $name): string
    {
        $name = ltrim($name, '\\/');
        $name = str_replace('/', '\\', $name);
        
        $rootNamespace = $this->rootNamespace();
        
        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }
        
        return $rootNamespace . '\\' . $name;
    }
    
    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace(): string
    {
        return app()->getNamespace();
    }
    
    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath(string $name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        
        if ($this->option('path')) {
            return $this->option('path') . '/' . str_replace('\\', '/', $name) . '.php';
        }
        
        return app_path(str_replace('\\', '/', $name) . '.php');
    }
    
    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(string &$stub, string $name): self
    {
        $searches = [
            '{{ namespace }}',
            '{{namespace}}',
        ];
        
        $namespace = Str::replaceLast('\\' . class_basename($name), '', $name);
        
        $stub = str_replace($searches, $namespace, $stub);
        
        return $this;
    }
    
    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceClass(string &$stub, string $name): self
    {
        $class = class_basename($name);
        
        $stub = str_replace(['{{ class }}', '{{class}}'], $class, $stub);
        
        return $this;
    }
    
    /**
     * Replace the stream name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $stream
     * @return $this
     */
    protected function replaceStream(string &$stub, string $stream): self
    {
        $stub = str_replace(['{{ stream }}', '{{stream}}'], $stream, $stub);
        
        return $this;
    }
}