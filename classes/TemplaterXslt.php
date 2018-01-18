<?php

/**
 * Превращает XML в вывод, определённый коллекцией шаблонов:
 *
 *      templates/<style>/<RequestName>.xsl
 *
 * Прежде, чем создавать первый инстанс, надо инициировать статичную переменную Compiler::$templatesRoot.
**/
class TemplaterXslt extends TemplaterAbstract implements TemplaterInterface
{

    // позже подумать над пакетной обработкой и кэшированием результатов.
    public function apply ($RequestName, $xmlString)
    {
        $xslPath = $this->templatesDirectory . '/' . $RequestName . '.xsl';
        if (! file_exists ($xslPath))
        {
            throw new Exception ('Не найден шаблон в стиле ' . $this->style . ' для запроса ' . $RequestName);
        }
        
        $xml = new DOMDocument();
        $xml->loadXML ($xmlString);
        
        $xsl = new DOMDocument();
        $xsl->substituteEntities = true;
        $xsl->load ($xslPath);
        
        $xslt = new XSLTProcessor();
        $xslt->importStylesheet ($xsl);
        return $xslt->transformToXML ($xml);
    }
}

?>
