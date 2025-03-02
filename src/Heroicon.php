<?php

namespace AlexAzartsev\Heroicon;

use Laravel\Nova\Fields\Field;
use Laravel\Nova\Http\Requests\NovaRequest;

class Heroicon extends Field
{
    public $component = 'fontawesome';
    public array $icons = [];

    protected static array $supportedSets = [];

    protected static array $defaultIcons = [];

    protected static array $defaultIconSets = [];
    protected static bool $defaultEditorEnabled = true;

    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);
        $this->registerDefaultIcons();
        $this->icons = self::$defaultIcons;
        $this->withMeta(['editor' => self::$defaultEditorEnabled]);
        $this->only(self::$defaultIconSets);
    }

    public function registerDefaultIcons()
    {
        foreach (self::$defaultIconSets as $defaultIconSet) {
            $alreadyRegistered = array_search($defaultIconSet, array_column(self::$defaultIcons, 'value'));
            $key = array_search($defaultIconSet, array_column(self::$supportedSets, 'value'));
            if ($alreadyRegistered === false && $key !== false) {
                $set = self::$supportedSets[$key];
                self::registerGlobalIconSet(
                    $set['value'],
                    $set['label'],
                    $set['path']
                );
            }
        }
    }

    public function disableEditor()
    {
        return $this->withMeta(['editor' => false]);
    }

    public function enableEditor()
    {
        return $this->withMeta(['editor' => true]);
    }

    public function onlySolid()
    {
        return $this->only(['solid']);
    }

    public function onlyOutline()
    {
        return $this->only(['outline']);
    }

    public function onlyFaBrands()
    {
        return $this->only(['fa-brands']);
    }

    public function onlyFaSolid()
    {
        return $this->only(['fa-solid']);
    }

    public function onlyFaRegular()
    {
        return $this->only(['fa-regular']);
    }

    public function registerIconSet(string $key, string $label, string $path)
    {
        $this->icons[] = [
            'value' => $key,
            'label' => $label,
            'icons' => $this->prepareIcons($key, $path)
        ];

        return $this->withMeta([
            'icons' => $this->icons
        ]);
    }

    public static function registerGlobalIconSet(string $key, string $label, string $path, bool|string $subType=false): void
    {
        $iconSet = [
            'value' => $key,
            'label' => $label,
        ];
        if (!$subType) {
            $iconSet['icons'] = self::prepareIcons($key, $path);
        }
        else {
            $iconSet['subType'] = $subType;
        }
        self::$defaultIcons[] = $iconSet;
    }

    public static function defaultIconSets(array $sets): void
    {
        self::$defaultIconSets = $sets;
    }

    public static function defaultEditorEnable(bool $enabled): void
    {
        self::$defaultEditorEnabled = $enabled;
    }


    protected static function prepareIcons($key, $path): array
    {
        $icons = [];
        $iconFileContents = file_get_contents($path);
        $iconArray = json_decode($iconFileContents);
        foreach($iconArray as $icon) {
            $icon->type ??= $key;
        }
        return $iconArray;
    }

    protected static function prepareIconsa($key, $path): array
    {
        $icons = [];
        $files = scandir($path);
        foreach ($files as $file) {
            if (preg_match("/.*\.svg/i", $file)) {
                $content = file_get_contents("$path/$file");
                $content = preg_replace('/<!--(.*?)-->/m', '', $content);
                $icons[] = [
                    'type'    => $key,
                    'name'    => strtolower(str_replace('.svg', '', $file)),
                    'content' => $content,
                ];
            }
        }

        return $icons;
    }

    public function only(array $sets)
    {
        $filteredIcons = [];
        foreach ($sets as $set) {
            $suportedSetKey = array_search($set, array_column(self::$supportedSets, 'value'));
            if (array_search($set, array_column($this->icons, 'value')) === false && $suportedSetKey !== false) {
                $supportedSet = self::$supportedSets[$suportedSetKey];
                $this->registerIconSet(
                    $supportedSet['value'],
                    $supportedSet['label'],
                    $supportedSet['path']
                );
            }
            foreach ($this->icons as $icon) {
                if ($icon['value'] === $set) {
                    $filteredIcons[] = $icon;
                }
            }
        }


        return $this->withMeta([
            'icons' => $filteredIcons
        ]);
    }

    public function jsonSerialize(): array {
        $returnArray = parent::jsonSerialize();
        $request = app(NovaRequest::class);
        if(!$request->isCreateOrAttachRequest() && !$request->isUpdateOrUpdateAttachedRequest()) {
            $returnArray['icons'] = null;
        }
        return $returnArray;
    }
}
