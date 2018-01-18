<?php

class HotelLister
{
    private $DataProvider = null;
    private $outputPath = '';
    
    public function __construct ($url, $secretPath, $formsPath, $outputPath)
    {
        try
        {
            $this->DataProvider = new DataProvider ($url, $secretPath, $formsPath);
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось получить провайдера: ' . $e->getMessage());
        }
        if (! file_exists ($outputPath))
        {
            mkdir ($outputPath, 0777, 1);
        }
        $this->outputPath = $outputPath;
    }

    // Все пары методов однотипные.
    // Переделать на __call ?
    
    // Countries
    
    public function getCountries ()
    {
        return $this->DataProvider->CountryListRequest ();
    }
    
    public function storeCountries ()
    {
        $dir = $this->outputPath . '/';
        if (! file_exists ($dir))
        {
            mkdir ($dir, 0777, 1);
        }
        try
        {
            file_put_contents (
                $dir . '/Countries.xml', 
                $result = $this->getCountries ()
            );
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось сохранить список стран: ' . $e->getMessage());
        }
        return $result;
    }
    
    // Cities By Country
    
    public function getCitiesByCountry ($CountryCode)
    {
        return $this->DataProvider->CityListRequest ($CountryCode);
    }
    
    public function storeCitiesByCountry ($CountryCode)
    {
        $dir = $this->outputPath . '/CitiesByCountry/';
        if (! file_exists ($dir))
        {
            mkdir ($dir, 0777, 1);
        }
        try
        {
            file_put_contents (
                $dir . '/' . $CountryCode . '.xml', 
                $result = $this->getCitiesByCountry ($CountryCode)
            );
        }
        catch (Exception $e)
        {
            throw new Exception (
                'Не удалось сохранить список городов для страны ' . $CountryCode . ': ' . 
                $e->getMessage()
            );
        }
        return $result;
    }
    
    // Hotels By City
    
    public function getHotelsByCity ($CityCode)
    {
        return $this->DataProvider->HotelListRequest ('', '', '', $CityCode);
    }
    
    public function storeHotelsByCity ($CityCode)
    {
        $dir = $this->outputPath . '/HotelsByCity';
        if (! file_exists ($dir))
        {
            mkdir ($dir, 0777, 1);
        }
        try
        {
            file_put_contents (
                $dir . '/' . $CityCode . '.xml', 
                $result = $this->getHotelsByCity ($CityCode)
            );
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось сохранить отели по городу ' . $CityCode . ': ' . $e->getMessage());
        }
        return $result;
    }

    // Hotel Description
    
    public function getHotelDescription ($HotelCode)
    {
        return $this->DataProvider->HotelDescriptionRequest ($HotelCode);
    }
    
    public function storeHotelDescription ($HotelCode)
    {
        $dir = $this->outputPath . '/HotelDescription';
        if (! file_exists ($dir))
        {
            mkdir ($dir, 0777, 1);
        }
        try
        {
            file_put_contents (
                $dir . '/' . $HotelCode . '.xml', 
                $result = $this->getHotelDescription ($HotelCode)
            );
        }
        catch (Exception $e)
        {
            throw new Exception ('Не удалось сохранить описание отеля ' . $HotelCode . ': ' . $e->getMessage());
        }
        return $result;
    }
}

?>
