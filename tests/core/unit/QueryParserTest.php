<?php

namespace Resty\Test;

use Resty\Exception\QueryException;
use Resty\Utility\QueryParser;

class QueryParserTest extends \PHPUnit_Framework_TestCase {

    public function testSearch() {
        $parser = new QueryParser();

        $parser->parseSearch(['name', 'description'], 'keyword');

        self::assertEquals(' (name LIKE ? OR description LIKE ?)', $parser->getConditionString());
        self::assertEquals('%keyword%', $parser->getConditionParams()[0]);
        self::assertEquals('%keyword%', $parser->getConditionParams()[1]);
    }

    public function testFilterInvalid() {
        $parser = new QueryParser();

        // Invalid field
        self::expectException(QueryException::class);
        $parser->parseFilter(['name', 'description'], 'other>=12');

        // Invalid equation
        self::expectException(QueryException::class);
        $parser->parseFilter(['name', 'description'], 'name12');

        // Too much equation
        self::expectException(QueryException::class);
        $parser->parseFilter(['name', 'description'], 'name<>=12');
    }

    public function testFilter() {
        $parser = new QueryParser();

        $parser->parseFilter(['name', 'description'], 'name=Janos,description<>leiras');

        self::assertEquals(' (name=? AND description<>?)', $parser->getConditionString());
        self::assertEquals('Janos', $parser->getConditionParams()[0]);
        self::assertEquals('leiras', $parser->getConditionParams()[1]);
    }

    public function testProjectionInvalid() {
        $parser = new QueryParser();

        // Invalid field
        self::expectException(QueryException::class);
        $parser->parseProjection(['name', 'description'], 'other');
    }

    public function testProjection() {
        $parser = new QueryParser();

        $parser->parseProjection(['name', 'description'], 'description');

        self::assertEquals(' description', $parser->getFieldList());
    }

    public function testSortingInvalid() {
        $parser = new QueryParser();

        // Invalid field
        self::expectException(QueryException::class);
        $parser->parseSorting(['name', 'description'], '-other');
    }

    public function testSorting() {
        $parser = new QueryParser();

        $parser->parseSorting(['name', 'description', 'date'], 'name,-date');

        self::assertEquals(' name ASC, date DESC', $parser->getSorting());
    }

}
