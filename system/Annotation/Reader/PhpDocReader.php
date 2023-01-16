<?php
namespace SlimEdge\Annotation\Reader;

use PhpDocReader\PhpDocReader as DocReader;
use PhpDocReader\PhpParser\UseStatementParser;
use ReflectionClass;

class PhpDocReader extends DocReader
{
    /**
     * @var UseStatementParser $parser
     */
    private $parser;

    private const PRIMITIVE_TYPES = [
        'bool' => 'bool',
        'boolean' => 'bool',
        'string' => 'string',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'float',
        'array' => 'array',
        'object' => 'object',
        'callable' => 'callable',
        'resource' => 'resource',
        'mixed' => 'mixed',
        'iterable' => 'iterable',
    ];

    public function __construct()
    {
        $this->parser = new UseStatementParser;
    }

    public function readClassProperties(ReflectionClass $class)
    {
        $comment = $class->getDocComment();

        if(!$comment) return null;

        $pattern = '/@property\s+(\\??(?:\\\?[a-zA-Z]\w*)+)\s+\$([a-zA-Z]\w*)/';
        if(false === preg_match_all($pattern, $comment, $matches)) {
            return null;
        }

        [, $types, $properties] = $matches;

        $result = [];
        for($i = 0; $i < count($types); $i++)
        {
            $type = $types[$i];
            $nullable = $type[0] === '?';
            if($nullable) $type = substr($type, 1);

            if(isset(self::PRIMITIVE_TYPES[$type])) {
                $type = self::PRIMITIVE_TYPES[$type];
            }
            elseif($type[0] !== '\\') {
                $type = $this->tryResolveFqn($type, $class);
            }

            $result[$properties[$i]] = [
                'property' => $properties[$i],
                'type'     => ltrim($type, '\\'),
                'nullable' => $nullable,
            ];
        }
        
        return $result;
    }

    /**
     * Attempts to resolve the FQN of the provided $type based on the $class and $member context.
     *
     * @return string|null Fully qualified name of the type, or null if it could not be resolved
     */
    private function tryResolveFqn(string $type, ReflectionClass $class): ?string
    {
        $alias = ($pos = strpos($type, '\\')) === false ? $type : substr($type, 0, $pos);
        $loweredAlias = strtolower($alias);

        // Retrieve "use" statements
        $uses = $this->parser->parseUseStatements($class);

        if (isset($uses[$loweredAlias])) {
            // Imported classes
            if ($pos !== false) {
                return $uses[$loweredAlias] . substr($type, $pos);
            }
            return $uses[$loweredAlias];
        }

        if ($this->classExists($class->getNamespaceName() . '\\' . $type)) {
            return $class->getNamespaceName() . '\\' . $type;
        }

        if (isset($uses['__NAMESPACE__']) && $this->classExists($uses['__NAMESPACE__'] . '\\' . $type)) {
            // Class namespace
            return $uses['__NAMESPACE__'] . '\\' . $type;
        }

        if ($this->classExists($type)) {
            // No namespace
            return $type;
        }

        return null;
    }

    private function classExists(string $class): bool
    {
        return class_exists($class) || interface_exists($class);
    }
}