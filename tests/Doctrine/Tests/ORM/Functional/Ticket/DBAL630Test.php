<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\Generic\BooleanModel;
require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DBAL-630
 */
class DBAL630Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $running = false;
    private $_conn;

    public function setUp()
    {
        $this->useModelSet('generic');
        parent::setUp();

        $this->_conn = $this->_em->getConnection();

        if (!in_array($this->_conn->getDatabasePlatform()->getName(), array('postgresql'))) {
            $this->markTestSkipped('Currently restricted to PostgreSQL');
        }

        $this->running = true;
    }

    public function tearDown()
    {
        if ($this->running) {
            $this->_conn->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->_conn->getWrappedConnection()->setAttribute(\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT, false);
        }
    }

    public function testBooleanConversionBoolParamEmulatedPrepares()
    {
        $this->_conn->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $this->_conn->getWrappedConnection()->setAttribute(\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT, true);

        $genericBoolean = new BooleanModel();
        $genericBoolean->booleanField = false;

        $this->_em->persist($genericBoolean);
        $this->_em->flush();

        $id = $genericBoolean->id;
        $this->assertNotEmpty($id);

        $dql = "SELECT a FROM Doctrine\Tests\Models\Generic\BooleanModel a WHERE a.id = :id";
        $fGenericBoolean = $this->_em->createQuery($dql)
                  ->setParameter('id', $id)
                  ->getSingleResult();

        $row = $this->_conn->fetchAssoc('SELECT booleanField FROM boolean_model WHERE id = ?', array($id));
        $this->assertEquals(false, $row['booleanfield']);
    }
}
