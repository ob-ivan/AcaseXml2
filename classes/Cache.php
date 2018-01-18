<?php

interface CacheInterface
{
    public static function exists ($key);
    
    public static function get ($key);
    
    public static function set ($key, $data);
}

class Cache implements CacheInterface
{
    // CacheInterface //
    
    public static function exists ($key)
    {
        return self::hashSaved (md5($key));
    }
    
    public static function get ($key)
    {
        return unserialize(self::getData(md5($key)));
    }
    
    public static function set ($key, $data)
    {
        self::saveData (md5($key), serialize($data));
    }
    
    // protected //
    
    protected static function hashSaved ($hash)
    {
        return file_exists (self::makePath ($hash));
    }

    protected static function makePath ($hash)
    {
        return CACHE_DIRECTORY . '/' . $hash[0] . '/' . $hash[1] . '/' . $hash[2] . '/' . $hash . '.txt';
    }
    
    protected static function getData ($hash)
    {
        $path = self::makePath ($hash);
        if (! file_exists ($path))
        {
            throw new Exception ('Попытка получить данные по несуществующему ключу: ' . $hash);
        }
        return file_get_contents ($path);
    }
    
    protected static function saveData ($hash, $data)
    {
        $path = self::makePath ($hash);
        $dir = dirname ($path);
        if (! file_exists ($dir))
        {
            mkdir ($dir, 0777, 1);
        }
        file_put_contents ($path, $data);
    }
}

?>
