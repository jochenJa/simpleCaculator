<?php

class CalculatorTest extends PHPUnit_Framework_TestCase
{
    private $expressies;
    private $expected;

    public function setUp()
    {
        $this->expected = <<<TREE
1 => 1
2.5 => 2.5
1 + 1 + 1 - 3 => 0
[1 + 1] => 2
[1 + [1]] => 2
[1 + [2 + 3]] + 4 => 10
[1 + 2 + 3] * 4 => 24
1 - 1 => 0
[1 + 1] * 5 => 10
52 - 15 => 37
3 + 1 * 2 => 5

TREE;

        $this->expressies = [
            '1',
            '2.5',
            '1 + 1 + 1 - 3',
            '[1 + 1]',
            '[1 + [1]]',
            '[1 + [2 + 3]] + 4',
            '[1 + 2 + 3] * 4',
            '1 - 1',
            '[1 + 1] * 5',
            '52 - 15',
            '3 + 1 * 2'
        ];
    }
    /**
     * @test
     */
    public function solves_expressions()
    {
        $actual = '';
        foreach($this->expressies as $expressie) {
            $actual .= sprintf("%s => %s\r\n",
                $expressie,
                (string)ExpressionSeperator::solve($expressie)
            );
        }
        $this->assertSame($this->expected, $actual);
    }
}

class ExpressionSeperator
{

    public static function solve($equation)
    {
        $subequations = [];
        if(preg_match('/\[(.*?)\]/', $equation, $subequations)) {
            foreach($subequations as $subEquation) {
                $innerEquation = preg_replace('/[\[\]]/','',$subEquation);
                $equation = str_replace(
                    $subEquation,
                    self::solve($innerEquation),
                    $equation
                );
            }
        }

        $parts = explode(' ', $equation);
        $expression = new Number($parts[0]);
        for($i = 1; $i < count($parts)-1; $i = $i + 2) {
            $solver = SolverFactory::buildSolver($parts[$i]);
            $expression = new Expression($solver, new Number($parts[$i+1]), $expression);
        }

        return $expression->result();
    }
}

class SolverFactory {

    public static function buildSolver($method)
    {
        switch($method) {
            case '+':
                return new Add();
            case '*':
                return new Multiply();
            case '-':
                return new Subtract();
        }

        return null;
    }
}

class Number implements ExpressionInterface
{
    /**
     * @var SolverInterface
     */
    private $number;

    /**
     * Number constructor.
     */
    public function __construct($number)
    {
        $this->number = floatval($number);
    }

    public function result()
    {
        return $this->number;
    }

}

class Expression implements ExpressionInterface
{
    public $left;
    private $solver;
    private $right;

    /**
     * Expression constructor.
     */
    public function __construct(SolverInterface $solver, ExpressionInterface $left, ExpressionInterface $right)
    {
        $this->solver = $solver;
        $this->left = $left;
        $this->right = $right;
    }

    public function result()
    {
        return $this->solver->solve($this->left->result(), $this->right->result());
    }
}

class Add implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input + $step;
    }
}

class Subtract implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input - $step;
    }
}

class Multiply implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input * $step;
    }
}

interface ExpressionInterface
{
    public function result();
}

interface SolverInterface
{
    public function solve($step, $input);
}
