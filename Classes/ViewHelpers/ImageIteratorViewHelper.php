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
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Fetches alternative images to be rendered inside an <img> tag or <picture> tag.
 *
 * = Examples =
 *
 * <code>
 *     <picture>
 *          <responseiveimages:imageiterator file="{yourFileReference}" as="alternativeFiles">
 *              <f:for each="{alternativeFiles}" as="alternativeFile">
 *                  <source media="(min-width: breakpoint)" srcset="{f:uri.image(src: alternativeFile.uid, treatIdAsReference: 1)}" />
 *              </f:for>
 *          </responsiveimages:imageiterator>
 *          <img class="{class}" alt="{alternative}" title="{title}" srcset="{f:uri.image(src:'{file}',treatIdAsReference:1,height: '{height}', width: '{width}', maxHeight: '{maxHeight}', maxWidth: '{maxWidth}')}" /
 *      </picture>
 * </code>
 * <output>
 *      <picture>
 *         <source media="(min-width: breakpoint)" srcset="../imageurl.png" />
 *         <img class="" alt="" title="" srcset="../imageurl.png">
 *      </picture>
 * </output>
 */
class ImageIteratorViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    protected $escapeChildren = false;
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('file', FileInterface::class, 'the file reference to be processed', true);
        $this->registerArgument('as', 'string', 'alternative files', false, 'alternatives');
        $this->registerArgument('order', 'array', 'the ordering of the items', false, ['xsmall', 'small', 'medium', 'large', 'xlarge']);
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
        foreach ($ordering as $orderLabel => $order) {
            if ($sortedAlternativeFiles[$orderLabel]) {
                $finalOrderings[$order] = $sortedAlternativeFiles[$orderLabel];
                // store it in case the next item is empty, so this one is used as well.
                $lastFile = $sortedAlternativeFiles[$orderLabel];
            } else {
                $finalOrderings[$order] = $lastFile;
            }
        }

        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add($fileNames, $finalOrderings);
        $output = $renderChildrenClosure();
        $templateVariableContainer->remove($fileNames);

        return $output;
    }
}
