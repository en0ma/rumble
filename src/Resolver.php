<?php

namespace en0ma\Rumble;

use Symfony\Component\Yaml\Yaml;

trait Resolver
{
    /**
     *  Get class names from files in migrations/seeds directory.
     *  For any class found require it, so we can create an instance.
     *
     * @param $dir
     * @return array
     * @throws \Exception
     */
    protected function getClasses($dir)
    {
        if (!file_exists($dir)) {
            throw new \Exception("{$dir} directory not found.");
        }

        $dirHandler  = opendir($dir);
        $classes = [];
        while (false != ($file = readdir($dirHandler))) {
            if ($file != "." && $file != "..") {
                require_once("$dir"."/".$file);
                $classes[] = $this->buildClass($file);;
            }
        }
        closedir($dirHandler);

        if (count($classes) == 0) {
            throw new \Exception("There are no {$dir} files run.");
        }
        return $classes;
    }

    /**
     *  Build class names from file name. This uses an underscore (_) convention.
     *  Each file in eigther the migrations or seeds folder, uses an underscore naming
     *  convention. eg: create_me_table => CreateMeTable (ClassName)
     *
     * @param $file
     * @return mixed
     */
    protected function buildClass($file)
    {
        $file = basename($file, '.php');
        $fileNameParts = explode('_', $file);

        foreach ($fileNameParts as &$part) {
            $part = ucfirst($part);
        }
        return implode('', $fileNameParts);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getConfig()
    {
        $configFile = 'rumble.php';
        if (!file_exists($configFile)) {
            throw new \Exception("The rumble.php configuration file is not found.");
        }

        ob_start();
        $configArray = include($configFile);
        ob_end_clean();

        if (!is_array($configArray)) {
            throw new \Exception("rumble PHP file must return an array.");
        }
        return $configArray;
    }

}