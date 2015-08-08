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
     *
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

    /**
     * @test
     */
    public function de_string_kan_opgedeeld_worden_in_betekenisvolle_stukken()
    {
        $expected = <<<TREE
Number(1)
Number(2.5)
Add Substract Multiply Divide
Bracket Number(1) Add Number(1)

TREE;
        $expressions = [
            ' 1 ',
            '2.5',
            '+-*/',
            '[1+ 1]'
        ];

        $actual = '';
        foreach($expressions as $expressie) {
            $actual .= sprintf("%s\r\n",
                implode(' ',ExpressionSeperator::breakUp($expressie))
            );
        }
        $this->assertSame($expected, $actual);
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

    public static function breakUp($expressie)
    {
        $parts = array_reverse(str_split($expressie));
        $expressions = [];
        $number = false;
        while(! empty($parts)) {
            $part = array_pop($parts);
            switch ($part) {
                case (preg_match('/[0-9.]/', $part) ? true : false):
                    if(! $number) {
                        $expressions[] = $number = new Number();
                    }
                    $number->add($part);
                    break;
                case (preg_match('/[+-\/*]/', $part) ? true : false):
                    $expressions[] = SolverFactory::buildSolver($part);
                    break;
                case (preg_match('/[\[\]]/', $part) ? true : false):
                    $expressions[] = new Bracket();
                    break;
                default:
                    $number = false;
            }
        }
        return $expressions;
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
            case '/':
                return new Divide();
        }

        return null;
    }
}

class Bracket implements SolverInterface
{

    public function solve($step, $input)
    {
        // TODO: Implement solve() method.
    }

    public function __toString()
    {
        return "Bracket";
    }
}

class Number implements ExpressionInterface
{
    private $number = '';

    public function result()
    {
        return (floatval($this->number));
    }

    public function __toString(){
        return 'Number('.$this->result().')';
    }

    public function add($number)
    {
        $this->number .= $number;
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

    public function __toString()
    {
        return "Add";
    }
}

class Subtract implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input - $step;
    }

    public function __toString()
    {
        return "Substract";
    }
}

class Multiply implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input * $step;
    }

    public function __toString()
    {
        return "Multiply";
    }
}

class Divide implements SolverInterface
{
    public function solve($step, $input)
    {
        return $input / $step;
    }

    public function __toString()
    {
        return "Divide";
    }

}

interface ExpressionInterface
{
    public function result();
}

interface SolverInterface
{
    public function solve($step, $input);

    public function __toString();
}
