<?php
require_once(dirname(__FILE__) . '/../../inc/baseCase.php');

/** test javax.cr.Session read methods (level 1)
 *  most of the pdf specification is in section 4.4 and 5.1
 *
 *  ExportTest: importXML, getImportContentHandler, exportSystemView, exportDocumentView
 *  NamespacesTest: getNamespacePrefix, getNamespacePrefixes, getNamespaceURI, setNamespacePrefix
 *
 *  level2: SessionWriteMethods: hasPendingChanges, getValueFactory, move, refresh, removeItem, save
 *  Retention: getRetentionManager
 *  Access Control: getAccessControlManager
 */

class Reading_5_DirectAccessMethodsTest extends phpcr_suite_baseCase
{
    static public function  setupBeforeClass()
    {
        parent::setupBeforeClass();
        self::$staticSharedFixture['ie']->import('general/base');
    }

    //5.1.1
    public function testGetRootNode()
    {
        $node = $this->sharedFixture['session']->getRootNode();
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals($node->getPath(), '/');
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRootNodeRepositoryException()
    {
        $this->markTestIncomplete('TODO: Figure out how to test this');
    }

    //5.1.3, 5.1.6
    public function testGetItem()
    {
        $node = $this->sharedFixture['session']->getItem('/tests_general_base');
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals($node->getName(), 'tests_general_base');

        $node = $this->sharedFixture['session']->getItem('/tests_general_base/index.txt');
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals($node->getName(), 'index.txt');

        $prop = $this->sharedFixture['session']->getItem('/tests_general_base/numberPropertyNode/jcr:content/foo');
        $this->assertInstanceOf('PHPCR\PropertyInterface', $prop);
        $this->assertEquals($prop->getName(), 'foo');
        $this->assertEquals($prop->getString(), 'bar');
    }

    //5.1.3, 5.1.6
    public function testGetNode()
    {
        $node = $this->sharedFixture['session']->getNode('/tests_general_base/numberPropertyNode');
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals('numberPropertyNode', $node->getName());

        $node = $this->sharedFixture['session']->getNode('/tests_general_base/index.txt');
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals('index.txt', $node->getName());
    }
    /**
     * Get something that is a property and not a node
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testGetNodeInvalid()
    {
        $this->sharedFixture['session']-> getNode('/tests_general_base/idExample/jcr:primaryType');
    }
    /**
     * Get something that is a node and not a property
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testGetPropertyInvalid()
    {
        $this->sharedFixture['session']-> getProperty('/tests_general_base/idExample');
    }

    //5.1.3, 5.1.6
    public function testGetProperty()
    {
        $prop = $this->sharedFixture['session']->getProperty('/tests_general_base/idExample/jcr:primaryType');
        $this->assertInstanceOf('PHPCR\PropertyInterface', $prop);
        $this->assertEquals($prop->getName(), 'jcr:primaryType');
        $this->assertEquals($prop->getString(), 'nt:file');
    }

    /**
     * it is forbidden to call getItem on the session with a relative path
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testGetItemRelativePathException()
    {
        $node = $this->sharedFixture['session']->getItem('tests_general_base');
    }

    /**
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testGetItemPathNotFound()
    {
        $this->sharedFixture['session']->getItem('/foobarmooh');
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
     public function testGetItemRepositoryException()
     {
         $this->sharedFixture['session']->getItem('//');
     }

     //5.1.2
    public function testItemExists()
    {
        $this->assertTrue($this->sharedFixture['session']->itemExists('/tests_general_base'));
        $this->assertFalse($this->sharedFixture['session']->itemExists('/foobar'));
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testItemExistsRelativePath()
    {
        $this->sharedFixture['session']->itemExists('tests_general_base');
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testItemExistsInvalidPath()
    {
        $this->sharedFixture['session']->itemExists('//');
    }


    public function testNodeExists()
    {
        $this->assertTrue($this->sharedFixture['session']->nodeExists('/tests_general_base'));
        $this->assertFalse($this->sharedFixture['session']->nodeExists('/foobar'));
        //a property is not a node
        $this->assertFalse($this->sharedFixture['session']->nodeExists('/tests_general_base/numberPropertyNode/jcr:content/foo'));
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testNodeExistsRelativePath()
    {
        $this->sharedFixture['session']->nodeExists('tests_general_base');
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testNodeExistsInvalidPath()
    {
        $this->sharedFixture['session']->nodeExists('//');
    }

    public function testPropertyExists()
    {
        $this->assertTrue($this->sharedFixture['session']->propertyExists('/tests_general_base/numberPropertyNode/jcr:content/foo'));
        //a node is not a property
        $this->assertFalse($this->sharedFixture['session']->propertyExists('/tests_general_base'));
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testPropertyExistsRelativePath()
    {
        $this->sharedFixture['session']->propertyExists('tests_general_base/numberPropertyNode/jcr:content/foo');
    }
    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testPropertyExistsInvalidPath()
    {
        $this->sharedFixture['session']->propertyExists('//');
    }

    public function testGetNodeByIdentifier()
    {
        $node = $this->sharedFixture['session']->getNodeByIdentifier('842e61c0-09ab-42a9-87c0-308ccc90e6f4');
        $this->assertInstanceOf('PHPCR\NodeInterface', $node);
        $this->assertEquals('/tests_general_base/idExample', $node->getPath());
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodeByIdentifierRepositoryException()
    {
        $this->sharedFixture['session']->getNodeByIdentifier('foo');
    }

    /**
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetNodeByIdentifierItemNotFoundException()
    {
        $this->sharedFixture['session']->getNodeByIdentifier('00000000-0000-0000-0000-000000000000'); //FIXME: is the identifier format defined by the repository? how to generically get a valid but inexistent id?
    }

    /**
     * spec 4.3
     * @expectedException JavaException
     */
    public function testImpersonate()
    {
        //TODO: Check if that's implemented in newer jackrabbit versions.
        //TODO: Write tests for LoginException and RepositoryException
        //$cr = $this->assertSimpleCredentials($this->sharedFixture['config']['user'], $this->sharedFixture['config']['pass']);
        //$ses = $this->sharedFixture['session']->impersonate($cr);
        $this->markTestIncomplete('TODO: check if we should implement this method.');

    }

    //4.4.4, 4.4.5
    public function testIsLiveLogout()
    {
        $ses = $this->assertSession($this->sharedFixture['config']);
        $this->assertTrue($ses->isLive());
        $ses->logout();
        $this->assertInstanceOf('PHPCR\SessionInterface', $ses);
        $this->assertFalse($ses->isLive());
    }

    public function testCheckPermission()
    {
        $this->sharedFixture['session']->checkPermission('/tests_general_base', 'read');
        $this->sharedFixture['session']->checkPermission('/tests_general_base/numberPropertyNode/jcr:content/foo', 'read');
    }
    /**
     * @expectedException \PHPCR\AccessControlException
     */
    public function testCheckPermissionAccessControlException()
    {
        $this->markTestIncomplete('TODO: how to produce a permission exception?');
        $this->sharedFixture['session']->checkPermission('/tests_general_base/numberPropertyNode/jcr:content/foo', 'add_node');
    }
    public function testHasPermission()
    {
        $this->assertTrue($this->sharedFixture['session']->hasPermission('/tests_general_base', 'read'));
        $this->assertTrue($this->sharedFixture['session']->hasPermission('/tests_general_base/numberPropertyNode/jcr:content/foo', 'read'));
        $this->assertTrue($this->sharedFixture['session']->hasPermission('/tests_general_base/numberPropertyNode/jcr:content/foo', 'add_node')); //we have permission, but this node is not capable of the operation
    }

    public function testHasCapability()
    {
        $node = $this->sharedFixture['session']->getNode('/tests_general_base');
        $this->assertTrue($this->sharedFixture['session']->hasCapability('getReferences', $node, array()), 'Does not have getReferences capability');
        $this->assertTrue($this->sharedFixture['session']->hasCapability('getProperty', $node, array('foo')), '2');
        $property = $this->sharedFixture['session']->getProperty('/tests_general_base/numberPropertyNode/jcr:content/foo');
        $this->assertTrue($this->sharedFixture['session']->hasCapability('getNode', $property, array()), '3');
        //$this->assertFalse($this->sharedFixture['session']->hasCapability('inexistentXXX', $property, array()), '4');
        //actually, the repository is not required to know, it can always say that the info can not be determined and return true. this makes me think that this method is pretty useless...
    }
}
