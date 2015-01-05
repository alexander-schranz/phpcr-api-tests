<?php
namespace PHPCR\Tests\Writing;

use PHPCR\WorkspaceInterface;

require_once(__DIR__ . '/../../inc/BaseCase.php');

/**
 * Covering jcr-283 spec $10.7
 */
class CopyMethodsTest extends \PHPCR\Test\BaseCase
{
    /** @var WorkspaceInterface */
    protected $ws;

    public static function setupBeforeClass($fixtures = '10_Writing/copy')
    {
        parent::setupBeforeClass($fixtures);
    }

    protected function setUp()
    {
        $this->renewSession(); // get rid of cache from previous tests
        parent::setUp();

        $this->ws = $this->session->getWorkspace();
    }

    public function testWorkspaceCopy()
    {
        $src = '/tests_write_manipulation_copy/testWorkspaceCopy/srcNode';
        $dst = '/tests_write_manipulation_copy/testWorkspaceCopy/dstNode/srcNode';

        $this->ws->copy($src, $dst);

        $snode = $this->session->getNode($src);
        $dnode = $this->session->getNode($dst);
        $this->assertNotEquals($snode->getIdentifier(), $dnode->getIdentifier());

        $schild = $this->session->getNode("$src/srcFile");
        $dchild = $this->session->getNode("$dst/srcFile");
        $this->assertNotEquals($schild->getIdentifier(), $dchild->getIdentifier());

        // do not save, workspace should do directly
        $this->renewSession();

        $this->assertTrue($this->session->nodeExists($dst));

        $snode = $this->session->getNode($src);
        $dnode = $this->session->getNode($dst);
        $this->assertNotEquals($snode->getIdentifier(), $dnode->getIdentifier(), 'UUID was not changed');

        $schild = $this->session->getNode("$src/srcFile");
        $dchild = $this->session->getNode("$dst/srcFile");
        $this->assertNotEquals($schild->getIdentifier(), $dchild->getIdentifier());

        $this->assertTrue($this->session->nodeExists($dst.'/srcFile/jcr:content'), 'Did not copy the whole subgraph');

        $sfile = $this->session->getNode("$src/srcFile/jcr:content");
        $dfile = $this->session->getNode("$dst/srcFile/jcr:content");
        $this->assertEquals($sfile->getPropertyValue('jcr:data'), $dfile->getPropertyValue('jcr:data'));

        $dfile->setProperty('jcr:data', 'changed content');
        $this->assertNotEquals($sfile->getPropertyValue('jcr:data'), $dfile->getPropertyValue('jcr:data'));
        $this->session->save();

        $this->saveAndRenewSession();

        $sfile = $this->session->getNode("$src/srcFile/jcr:content");
        $dfile = $this->session->getNode("$dst/srcFile/jcr:content");
        $this->assertNotEquals($sfile->getPropertyValue('jcr:data'), $dfile->getPropertyValue('jcr:data'));
    }

    public function testWorkspaceCopyReference()
    {
        $src = '/tests_write_manipulation_copy/testWorkspaceCopy/referencedNodeSet';
        $dst = '/tests_write_manipulation_copy/testWorkspaceCopy/dstNode/copiedReferencedSet';

        $this->ws->copy($src, $dst);

        $snode = $this->session->getNode($src);
        $dnode = $this->session->getNode($dst);

        $this->assertNotEquals($snode->getIdentifier(), $dnode->getIdentifier());

        $homeNode = $dnode->getNode('home');
        $block1Ref = $homeNode->getProperty('block_1')->getValue()->getIdentifier();
        $block2Ref = $homeNode->getProperty('block_2')->getValue()->getIdentifier();
        $block3Ref = $homeNode->getProperty('block_2')->getValue()->getIdentifier();

        $externalRef = $homeNode->getProperty('external_reference')->getValue()->getIdentifier();

        $this->assertEquals('842e61c0-09ab-42a9-87c0-308ccc90e6f6', $externalRef);

        $block1 = $homeNode->getNode('block_1');
        $this->assertEquals($block1->getIdentifier(), $block1Ref);

        $block2 = $block1->getNode('block_2');
        $this->assertEquals($block2->getIdentifier(), $block2Ref);

        // weak reference
        $block3 = $block1->getNode('block_3');
        $this->assertEquals($block2->getIdentifier(), $block2Ref);
    }

    public function testWorkspaceCopyOther()
    {
        self::$staticSharedFixture['ie']->import('general/additionalWorkspace', 'additionalWorkspace');
        $src = '/tests_additional_workspace/testWorkspaceCopyOther/node';
        $dst = '/tests_write_manipulation_copy/testWorkspaceCopyOther/foobar';

        $this->ws->copy($src, $dst, self::$loader->getOtherWorkspaceName());
        $node = $this->session->getNode($dst);
        $this->assertTrue($node->hasProperty('x'));
        $this->assertEquals('y', $node->getPropertyValue('x'));
    }

    /**
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testCopyNoSuchWorkspace()
    {
        $src = '/somewhere/foo';
        $dst = '/here/foo';
        $this->ws->copy($src, $dst, 'inexistentworkspace');
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testCopyRelativePaths()
    {
        $this->ws->copy('foo/moo', 'bar/mar');
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testCopyInvalidDstPath()
    {
        $src = '/tests_write_manipulation_copy/testCopyInvalidDstPath/srcNode';
        $dst = '/tests_write_manipulation_copy/testCopyInvalidDstPath/dstNode/srcNode[3]';
        $this->ws->copy($src, $dst);
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testCopyProperty()
    {
        $src = '/tests_write_manipulation_copy/testCopyProperty/srcFile/jcr:content/someProperty';
        $dst = '/tests_write_manipulation_copy/testCopyProperty/dstFile/jcr:content/someProperty';
        $this->ws->copy($src, $dst);
    }

    /**
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testCopySrcNotFound()
    {
        $src = '/tests_write_manipulation_copy/testCopySrcNotFound/notFound';
        $dst = '/tests_write_manipulation_copy/testCopySrcNotFound/dstNode/notFound';
        $this->ws->copy($src, $dst);
    }

    /**
     * @expectedException \PHPCR\PathNotFoundException
     */
    public function testCopyDstParentNotFound()
    {
        $src = '/tests_write_manipulation_copy/testCopyDstParentNotFound/srcNode';
        $dst = '/tests_write_manipulation_copy/testCopyDstParentNotFound/dstNode/notFound/srcNode';
        $this->ws->copy($src, $dst);
    }

    /**
     * Verifies that there is no update-on-copy if the target node already exists
     *
     * @expectedException \PHPCR\ItemExistsException
     */
    public function testCopyNoUpdateOnCopy()
    {
        $src = '/tests_write_manipulation_copy/testCopyNoUpdateOnCopy/srcNode';
        $dst = '/tests_write_manipulation_copy/testCopyNoUpdateOnCopy/dstNode/srcNode';

        $this->ws->copy($src, $dst);
    }

    public function testCopyUpdateOnCopy()
    {
        $src = '/tests_write_manipulation_copy/testCopyUpdateOnCopy/srcNode';
        $dst = '/tests_write_manipulation_copy/testCopyUpdateOnCopy/dstNode/srcNode';
        $this->ws->copy($src, $dst);

        // make sure child node was copied
        $this->assertTrue($this->session->nodeExists($dst.'/srcFile'));
        // make sure things were updated
        $this->assertEquals('123', $this->session->getProperty($dst.'/updateFile/jcr:data')->getValue());
    }

}
