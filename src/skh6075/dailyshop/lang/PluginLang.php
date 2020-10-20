<?php


namespace skh6075\dailyshop\lang;



class PluginLang{

    private $lang = "kor";
    
    private $translates = [];
    
    
    public function __construct () {
    }
    
    public function setLang (string $lang): PluginLang{
        $this->lang = $lang;
        return $this;
    }
    
    public function setTranslates (array $translates = []): PluginLang{
        $this->translates = $translates;
        return $this;
    }
    
    public function translate (string $key, array $replaces = [], bool $pushPrefix = true): string{
        $translate = $pushPrefix ? $this->translates ["prefix"] : "";
        $translate .= $this->translates [$key];
        
        foreach ($replaces as $old => $new) {
            $translate = str_replace ($old, $new, $translate);
        }
        return $translate;
    }
}