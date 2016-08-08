<?php
namespace CMSExperts\Responsiveimages\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Fetches alternative images to be rendered inside an <img> tag or <picture> tag.
 *
 * = Examples =
 *
 * <code>
 *     <responseiveimages:imageiterator file="{yourFileReference}" as="alternativeFiles">
 *          <picture>
 *              <item><f:uri.image src="{yourFileReference}"></item>
 *              <item><f:uri.image src="{yourFileReference}" class="xlarge"></item>
 *          </picture>
 *      </responsiveimages:imageiterator>
 * </code>
 * <output>
 *      <picture>
 *          <img alt="alt text" src="../typo3conf/ext/myext/Resources/Public/typo3_logo.png" />
 *      </picture>
 * </output>
 */
class ImageIteratorViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('file', AbstractFile::class, 'the file reference to be processed', true);
        $this->registerArgument('as', 'string', 'alternative files', false, 'alternatives');
        $this->registerArgument('order', 'array', 'the ordering of the items', false, ['xlarge', 'large', 'medium', 'small', 'xsmall']);
    }

    /**
     * Get alternative files
     *
     * @return string the contents of the viewhelper
     */
    public function render()
    {
        return static::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        /** @var AbstractFile $file */
        $file = $arguments['file'];
        $fileNames = $arguments['as'];
        $ordering = $arguments['order'];

        $originalRecord = [
            'uid' => $file->getUid()
        ];

        // check if it is a translation
        if ($file->getProperty('sys_language_uid')) {
            $originalRecord['_LOCALIZED_UID'] = $file->getProperty('sys_language_uid');
        }

        // fetch the files
        $alternativeFiles = $GLOBALS['TSFE']->sys_page->getFileReferences('sys_file_reference', 'alternativefile', $originalRecord);
        $sortedAlternativeFiles = [];
        foreach ($alternativeFiles as $alternativeFile) {
            $label = $alternativeFile->getProperty('alternativetag');
            $sortedAlternativeFiles[$label] = $alternativeFile;
        }

        $finalOrderings = [];
        // from bottom to top
        $ordering = array_flip($ordering);
        $lastFile = $file;
        foreach ($ordering as $order) {
            if ($sortedAlternativeFiles[$order]) {
                $finalOrderings[$order] = $sortedAlternativeFiles[$order];
                // store it in case the next item is empty, so this one is used as well.
                $lastFile = $sortedAlternativeFiles[$order];
            } else {
                $finalOrderings[$order] = $lastFile;
            }
        }

        $finalOrderings = array_flip($finalOrderings);

        $templateVariableContainer = $renderingContext->getTemplateVariableContainer();
        $templateVariableContainer->add($fileNames, $finalOrderings);
        $output = $renderChildrenClosure;
        $templateVariableContainer->remove($fileNames);

        return $output;
    }
}
