<?php

declare(strict_types=1);

namespace Tests\Forge;

use App\Dao\ForgeProjectDao;
use PDO;
use PHPUnit\Framework\TestCase;

final class ForgeProjectDaoTest extends TestCase
{
    private PDO $pdo;
    private ForgeProjectDao $dao;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dao = new ForgeProjectDao($this->pdo);
        $this->dao->createTableIfNotExists();
    }

    public function testUpsertInsertAndUpdateByPath(): void
    {
        $a = $this->dao->upsert('demo', 'D:/laragon/www/demo', 'blank');
        self::assertSame(1, $a->id);
        self::assertSame('demo', $a->name);
        self::assertSame('blank', $a->recipe);

        $b = $this->dao->upsert('demo-renamed', 'D:/laragon/www/demo', 'blog-lite');
        self::assertSame(1, $b->id);
        self::assertSame('demo-renamed', $b->name);
        self::assertSame('blog-lite', $b->recipe);
        self::assertCount(1, $this->dao->allOrdered());
    }

    public function testDeleteRemovesRow(): void
    {
        $p = $this->dao->upsert('x', 'D:/tmp/x', 'api-json');
        self::assertTrue($this->dao->delete($p->id));
        self::assertNull($this->dao->find($p->id));
    }

    public function testPathNormalization(): void
    {
        $this->dao->upsert('a', 'D:\\laragon\\www\\app\\', 'blank');
        $found = $this->dao->findByPath('D:/laragon/www/app');
        self::assertNotNull($found);
        self::assertSame('D:/laragon/www/app', $found->path);
    }
}
