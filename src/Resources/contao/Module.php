<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Module;
use Contao\System;
use Contao\StringUtil;

/**
 * ApiModule augments ModuleModel for the API.
 */
class ApiModule extends AugmentedContaoModel
{
    public $article;
    public $compiledHTML;
    public $template;
    /**
     * constructor.
     *
     * @param int $id id of the ModuleModel
     */
    public function __construct($id, $url = null)
    {
        $readers = System::getContainer()->getParameter('content_api_readers');
        $this->model = ModuleModel::findByPk($id);
        $moduleClass = Module::findClass($this->type);
        try {
            $strColumn = null;
            // Add compatibility to new front end module fragments
            if (defined('VERSION')) {
                if (version_compare(VERSION, '4.5', '>=')) {
                    if ($moduleClass === \Contao\ModuleProxy::class) {
                        $strColumn = 'main';
                    }
                }
            }
            $r = new \ReflectionMethod($moduleClass, 'compile');
            $r->setAccessible(true);
            $module = new $moduleClass($this->model, $strColumn);
            $module->Template = new FrontendTemplate();
            $r->invoke($module);
            $this->template = $module->Template;
            if (System::getContainer()->getParameter('content_api_compile_html'))
                $this->compiledHTML = @$module->generate() ?? null;
        } catch (\Exception $e) {
            $this->template = null;
            $this->compiledHTML = null;
        }

        if ($url !== null) {
            foreach ($readers as $type => $model) {
                if ($this->type == $type) {
                    $this->article = new Reader($model, $url);
                    if (!$this->article->size || ($this->imgSize && !trim(
                        implode(
                            '',
                            StringUtil::deserialize($this->article->size)
                        )
                    ))) $this->article->size = $this->imgSize;
                }
            }
        }

        if (isset($GLOBALS['TL_HOOKS']['apiModuleGenerated']) && is_array($GLOBALS['TL_HOOKS']['apiModuleGenerated'])) {
            foreach ($GLOBALS['TL_HOOKS']['apiModuleGenerated'] as $callback) {
                $callback[0]::{$callback[1]}($this, $moduleClass);
            }
        }
    }
}
