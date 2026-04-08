<?php declare(strict_types = 1);

// odsl-/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-toolkit-dokploy-database/src/DokployDatabaseToolkit.php-PHPStan\BetterReflection\Reflection\ReflectionClass-CoquiBot\Toolkits\DokployDatabase\DokployDatabaseToolkit
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.18-54a5f4c3477e27f6066108c3b647ca719dba0f429a9d80429d2ed01c80df29aa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'filename' => '/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-toolkit-dokploy-database/src/DokployDatabaseToolkit.php',
      ),
    ),
    'namespace' => 'CoquiBot\\Toolkits\\DokployDatabase',
    'name' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
    'shortName' => 'DokployDatabaseToolkit',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Dokploy database management toolkit for Coqui.
 *
 * Provides full lifecycle management for Dokploy-managed databases
 * (Postgres, MySQL, MariaDB, MongoDB, Redis), database-specific
 * backup policies, and S3-compatible backup destinations.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 117,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'CarmeloSantana\\PHPAgents\\Contract\\ToolkitInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'client' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'implementingClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'name' => 'client',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'CoquiBot\\Toolkits\\Dokploy\\Runtime\\DokployClient',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 43,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'client' => 
          array (
            'name' => 'client',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 25,
                'endLine' => 25,
                'startTokenPos' => 77,
                'startFilePos' => 798,
                'endTokenPos' => 77,
                'endFilePos' => 801,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'CoquiBot\\Toolkits\\Dokploy\\Runtime\\DokployClient',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 9,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 24,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CoquiBot\\Toolkits\\DokployDatabase',
        'declaringClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'implementingClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'currentClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'aliasName' => NULL,
      ),
      'tools' => 
      array (
        'name' => 'tools',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<\\CarmeloSantana\\PHPAgents\\Contract\\ToolInterface>
 */',
        'startLine' => 33,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CoquiBot\\Toolkits\\DokployDatabase',
        'declaringClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'implementingClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'currentClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'aliasName' => NULL,
      ),
      'guidelines' => 
      array (
        'name' => 'guidelines',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 42,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CoquiBot\\Toolkits\\DokployDatabase',
        'declaringClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'implementingClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'currentClassName' => 'CoquiBot\\Toolkits\\DokployDatabase\\DokployDatabaseToolkit',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));