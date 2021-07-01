<?php

namespace DieSchittigs\ContaoContentApiBundle;

use Contao\ContentModel;
use Contao\FormModel;
use Contao\FormFieldModel;
use Contao\ContentElement;
use Contao\Controller;

/**
 * ApiContentElement augments ContentModel for the API.
 */
class ApiContentElement extends AugmentedContaoModel
{
    /**
     * constructor.
     *
     * @param int    $id       id of the ContentModel
     * @param string $inColumn In which column does the Content Element reside in
     */
    public function __construct($id, $inColumn = 'main')
    {
        $this->model = ContentModel::findById($id, ['published'], ['1']);
        if (!$this->model || !Controller::isVisibleElement($this->model)) {
            return $this->model = null;
        }
        $this->compiledHtml = null;
        $ceClass = 'Contao\Content'.ucfirst($this->model->type);
        if (class_exists($ceClass)) {
            try {
                $compiled = new $ceClass($this->model, $inColumn);
                $this->compiledHtml = $compiled->generate();
            } catch (\Exception $e) {
            }
        }
        if ($this->type === 'module') {
            $contentModuleClass = ContentElement::findClass($this->type);
            $element = new $contentModuleClass($this->model, $inColumn);
            $this->subModule = new ApiModule($element->module);
        }
        if ($this->type === 'form') {
            $formModel = FormModel::findById($this->form);
            if ($formModel) {
                $formModel->fields = FormFieldModel::findPublishedByPid($formModel->id);
            }
            $this->subForm = $formModel;
        }
        if ($this->type === 'ce_ort_iconnavigation') {
            $iconBoxes = empty($this->model->iconBoxes) ? [] : unserialize($this->model->iconBoxes);
            foreach ($iconBoxes as $i => $box) {
                if ($box['image']) {
                    $iconBoxes[$i]['image'] = (new File($box['image'], null))->toJson();
                }
            }
            $this->iconBoxes = array_map(function ($item) {
                return [
                    'title' => $item['title'],
                    'text' => $item['text'],
                    'image' => $item['image'],
                ];
            }, $iconBoxes);
        }
    }

    /**
     * Select by Parent ID and Table.
     *
     * @param int    $pid      Parent ID
     * @param string $table    Parent table
     * @param string $inColumn In which column doe the Content Elements reside in
     */
    public static function findByPidAndTable($pid, $table = 'tl_article', $inColumn = 'main')
    {
        $contents = [];
        $contentModels = ContentModel::findPublishedByPidAndTable($pid, $table, ['order' => 'sorting ASC']);
        if (!$contentModels) {
            return $contents;
        }
        foreach ($contentModels  as $content) {
            if (!Controller::isVisibleElement($content)) {
                continue;
            }
            $contents[] = new self($content->id, $inColumn);
        }

        return $contents;
    }

    /**
     * Does this Content Element have a reader module?
     *
     * @param string $readerType What kind of reader? e.g. 'newsreader'
     */
    public function hasReader($readerType): bool
    {
        return $this->subModule && $this->subModule->type == $readerType;
    }
}
