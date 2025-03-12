<?php

namespace WizardingCode\RedisStream\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeConsumerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis-stream:make-consumer 
                            {name : The name of the consumer class}
                            {--stream= : The stream name for this consumer}
                            {--group= : The consumer group name}
                            {--path= : The path where the consumer class should be created}
                            {--command : Create an Artisan command to run this consumer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Redis Stream consumer class';

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
        $baseName = class_basename($name);
        $stream = $this->option('stream') ?? Str::snake($baseName);
        $group = $this->option('group') ?? Str::snake($baseName) . '_group';
        
        $stub = $this->getStub();
        $className = $this->qualifyClass($name);
        $path = $this->getPath($className);
        
        // Check if class already exists
        if ($this->files->exists($path) && !$this->confirm("The file {$path} already exists. Do you want to overwrite it?")) {
            $this->error('Consumer creation cancelled.');
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
                     ->replaceStream($stub, $stream)
                     ->replaceGroup($stub, $group);
        
        // Write the file
        $this->files->put($path, $stub);
        
        $this->info("Consumer created successfully: {$className}");
        
        // Create an Artisan command if requested
        if ($this->option('command')) {
            $this->createConsumerCommand($name, $stream, $group);
        }
        
        return 0;
    }
    
    /**
     * Create a command to run the consumer.
     *
     * @param string $consumerName The consumer class name
     * @param string $stream The stream name
     * @param string $group The consumer group name
     * @return void
     */
    protected function createConsumerCommand(string $consumerName, string $stream, string $group): void
    {
        $baseName = class_basename($consumerName);
        $commandName = 'Console/Commands/' . $baseName . 'Command.php';
        $commandClass = $this->qualifyClass($commandName);
        $path = $this->getPath($commandClass);
        
        // Check if command already exists
        if ($this->files->exists($path) && !$this->confirm("The command file {$path} already exists. Do you want to overwrite it?")) {
            $this->error('Command creation cancelled.');
            return;
        }
        
        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }
        
        // Get command stub
        $stub = $this->files->get(__DIR__ . '/../../stubs/consumer-command.stub');
        
        // Replace placeholders in command stub
        $namespace = Str::replaceLast('\\' . class_basename($commandClass), '', $commandClass);
        $commandClassName = class_basename($commandClass);
        $consumerClassName = $this->qualifyClass($consumerName);
        
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ consumer_class }}', '{{ stream }}', '{{ group }}'],
            [$namespace, $commandClassName, $consumerClassName, $stream, $group],
            $stub
        );
        
        // Write the command file
        $this->files->put($path, $stub);
        
        $this->info("Consumer command created successfully: {$commandClass}");
    }
    
    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return $this->files->get(__DIR__ . '/../../stubs/consumer.stub');
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
    
    /**
     * Replace the group name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $group
     * @return $this
     */
    protected function replaceGroup(string &$stub, string $group): self
    {
        $stub = str_replace(['{{ group }}', '{{group}}'], $group, $stub);
        
        return $this;
    }
}