<?php

namespace Fromholdio\CMSFieldsScaffolder;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;

class CMSFieldsScaffolder extends Extension
{
    private static $scaffolder = [
        'tabs' => null,
        'do_clear_empty_tabs' => true,
        'content_field_tab_path' => 'Root.ContentTabSet.ContentMainTab',
        'root_main_tab_path' => 'Root.MainTabSet.MainTab',
        'fields_to_remove' => null
    ];

    public function runCMSFieldsScaffolderBeforeUpdate(FieldList $fields): FieldList
    {
        $fields = $this->getOwner()->applyCMSFieldsScaffolderTabs($fields);
        $fields = $this->getOwner()->applyCMSFieldsScaffolderContentFieldTabPath($fields);
        return $fields;
    }

    public function runCMSFieldsScaffolderAfterUpdate(FieldList $fields): FieldList
    {
        $fields = $this->getOwner()->applyCMSFieldsScaffolderRootMainTabPath($fields);
        $fields = $this->getOwner()->clearCMSFieldsScaffolderEmptyTabs($fields);
        return $fields;
    }

    public function getCMSFieldsScaffolderSetting(string $key)
    {
        $scaffolder = $this->getOwner()->config()->get('scaffolder');
        return $scaffolder[$key] ?? null;
    }

    public function applyCMSFieldsScaffolderTabs(FieldList $fields): FieldList
    {
        $tabs = $this->getOwner()->getCMSFieldsScaffolderSetting('tabs');
        if (empty($tabs)) {
            return $fields;
        }
        foreach ($tabs as $tabSetName => $children)
        {
            if (empty($children)) {
                continue;
            }
            if (is_array($children))
            {
                $field = TabSet::create($tabSetName, $this->getOwner()->fieldLabel($tabSetName));
                foreach ($children as $key => $tabName)
                {
                    if (empty($tabName)) {
                        continue;
                    }
                    $tab = Tab::create($tabName, $this->getOwner()->fieldLabel($tabName));
                    $field->push($tab);
                }
            }
            else {
                $field = Tab::create($tabSetName, $this->getOwner()->fieldLabel($tabSetName));
            }
            $fields->addFieldToTab('Root', $field);
        }
        return $fields;
    }

    public function applyCMSFieldsScaffolderContentFieldTabPath(FieldList $fields): FieldList
    {
        $newTabPath = $this->getOwner()->getCMSFieldsScaffolderSetting('content_field_tab_path');
        if ($newTabPath === false) {
            $fields->removeByName('Content');
            return $fields;
        }
        if (empty($newTabPath)) {
            return $fields;
        }
        $contentField = $fields->dataFieldByName('Content');
        if ($contentField) {
            $fields->removeByName($contentField->getName());
            $tab = $fields->findOrMakeTab($newTabPath);
            $tab->push($contentField);
        }
        return $fields;
    }

    public function applyCMSFieldsScaffolderRootMainTabPath(FieldList $fields): FieldList
    {
        $newTabPath = $this->getOwner()->getCMSFieldsScaffolderSetting('root_main_tab_path');
        if (empty($newTabPath)) {
            return $fields;
        }
        /** @var Tab $mainTab */
        $mainTab = $fields->fieldByName('Root.Main');
        if ($mainTab)
        {
            $newMainTab = $fields->findOrMakeTab($newTabPath);
            $mainFields = $mainTab->Fields();
            $fields->removeByName($mainTab->getName());
            foreach ($mainFields as $mainField) {
                $newMainTab->push($mainField);
            }
        }
        return $fields;
    }

    public function clearCMSFieldsScaffolderEmptyTabs(FieldList $fields): FieldList
    {
        $doClearEmpties = $this->getOwner()->getCMSFieldsScaffolderSetting('do_clear_empty_tabs');
        if ($doClearEmpties !== true) {
            return $fields;
        }
        $rootTabSet = $fields->fieldByName('Root');
        if (!$rootTabSet) {
            return $fields;
        }
        $rootTabs = $rootTabSet->Tabs();
        foreach ($rootTabs as $rootTab)
        {
            if (is_a($rootTab, TabSet::class))
            {
                $tabs = $rootTab->Tabs();
                /** @var Tab $tab */
                foreach ($tabs as $tab) {
                    if (!is_a($tab, TabSet::class) && $tab->Fields()->count() < 1) {
                        $tabs->removeByName($tab->getName());
                    }
                }
                if ($rootTab->Tabs()->count() < 1) {
                    $rootTabs->removeByName($rootTab->getName());
                }
            }
            elseif (is_a($rootTab, Tab::class))
            {
                if ($rootTab->Fields()->count() < 1) {
                    $rootTabs->removeByName($rootTab->getName());
                }
            }
        }
        return $fields;
    }

    public function removeCMSFieldsScaffolderFields(FieldList $fields): FieldList
    {
        $fieldsToRemove = $this->getOwner()->getCMSFieldsScaffolderSetting('fields_to_remove');
        if (empty($fieldsToRemove)) {
            return $fields;
        }
        foreach ($fieldsToRemove as $key => $fieldName) {
            if (empty($fieldName)) {
                continue;
            }
            $fields->removeByName($fieldName);
        }
        return $fields;
    }
}
