<?php

namespace App\Traits;

trait ModelsIteration
{
    private function getModels($path)
    {
        $out = [];
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;
            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                $out = array_merge($out, $this->getModels($filename));
            } else{
                $className = explode('app\\', $filename)[1];
                $out[] = str_replace(['.php', '/'], ['', '\\'], 'App/'.$className);
            }
        }
        return $out;
    }
}
