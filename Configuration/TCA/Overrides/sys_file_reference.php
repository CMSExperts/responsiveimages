<?php
defined('TYPO3_MODE') or die();

// only show the cropping field for records that have no localization parent
call_user_func(function () {
    $additionalColumn = [
        'alternativefile' => [
            'label' => 'Choose alternative files',
            'displayCond' => 'FIELD:tablenames:!=:sys_file_reference',
            'l10n_mode' => 'exclude',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'alternativefile',
                [
                    'foreign_types' => $GLOBALS['TCA']['tt_content']['columns']['assets']['config']['foreign_types']
                ],
                $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
            )
        ],
        'alternativetag' => [
            'label' => 'Label (e.g. "media queries")',
            'config' => [
                'type' => 'select',
                'items' => [
                    ['Extra Large', 'xlarge'],
                    ['Small', 'large'],
                    ['Medium', 'medium'],
                    ['Small', 'small'],
                    ['Extra Small', 'xsmall']
                ],
                'size' => 10
            ]
        ]
    ];
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_reference', $additionalColumn);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('sys_file_reference', 'basicoverlayPalette', '--linebreak--,alternativetag');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('sys_file_reference', 'imageoverlayPalette', '--linebreak--,alternativetag,--linebreak--,alternativefile');

    // change label in the IRRE title bar
    $GLOBALS['TCA']['sys_file_reference']['ctrl']['formattedLabel_userFunc'] = CMSExperts\Responsiveimages\Service\UserFileInlineLabelService::class . '->getInlineLabel';
});
