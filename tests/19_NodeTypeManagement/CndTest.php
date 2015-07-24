<?php

/*
 * This file is part of the PHPCR API Tests package
 *
 * Copyright (c) 2013 Liip and others
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPCR\Tests\NodeTypeManagement;

/**
 * Test the compact nodetype definition format registering
 * added in JCR 2.1.
 */
class CndTest extends NodeTypeBaseCase
{
    protected function registerNodeTypes($allowUpdate)
    {
        $ntm = $this->workspace->getNodeTypeManager();

        return $ntm->registerNodeTypesCnd($this->cnd, $allowUpdate);
    }

    protected function registerNodeTypePrimaryItem()
    {
        $ntm = $this->workspace->getNodeTypeManager();

        return $ntm->registerNodeTypesCnd($this->primary_item_cnd, true);
    }

    protected function registerBuiltinNodeType()
    {
        $ntm = $this->workspace->getNodeTypeManager();

        return $ntm->registerNodeTypesCnd($this->system_cnd, true);
    }

    private $cnd = "
        <'phpcr'='http://www.doctrine-project.org/projects/phpcr_odm'>
         [phpcr:apitest]
          mixin
          - phpcr:class (string) multiple
          [phpcr:test]
          mixin
          - phpcr:prop (string)
          ";

    private $primary_item_cnd = "
        <'phpcr'='http://www.doctrine-project.org/projects/phpcr_odm'>
        [phpcr:primary_item_test]
        - phpcr:content (string)
        primary
        ";

    private $system_cnd = "
        <'nt'='http://www.jcp.org/jcr/nt/1.0'>
        [nt:file]
        - x (string)
    ";
}
