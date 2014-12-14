<?php

/**
 * This file is automatically generated. Use 'arc liberate' to rebuild it.
 *
 * @generated
 * @phutil-library-version 2
 */
phutil_register_library_map(array(
  '__library_version__' => 2,
  'class' => array(
    'ArgumentsVisitor' => 'shell/visitor/ArgumentsVisitor.php',
    'AssignmentVisitor' => 'shell/visitor/AssignmentVisitor.php',
    'Builtin' => 'builtin/Builtin.php',
    'BuiltinLaunchable' => 'shell/launchable/BuiltinLaunchable.php',
    'ByteStreamEndpointTestCase' => '__tests__/ByteStreamEndpointTestCase.php',
    'CommandParser' => 'parser/CommandParser.php',
    'CommandVisitor' => 'shell/visitor/CommandVisitor.php',
    'DigitToken' => 'parser/lexer/LetterToken.php',
    'DoubleQuotedVisitor' => 'shell/visitor/DoubleQuotedVisitor.php',
    'Endpoint' => 'pipe/Endpoint.php',
    'EndpointTestCase' => '__tests__/EndpointTestCase.php',
    'Expected' => 'parser/state/Expected.php',
    'FragmentVisitor' => 'shell/visitor/FragmentVisitor.php',
    'FragmentsVisitor' => 'shell/visitor/FragmentsVisitor.php',
    'HasTerminalModesInterface' => 'shell/HasTerminalModesInterface.php',
    'InboundFDStream' => 'pipe/InboundFDStream.php',
    'InboundStream' => 'pipe/InboundStream.php',
    'InteractiveTTYEditline' => 'interactive/InteractiveTTYEditline.php',
    'InteractiveTTYManual' => 'interactive/InteractiveTTYManual.php',
    'InteractiveTTYNCursesPartial' => 'interactive/InteractiveTTYNCursesPartial.php',
    'Job' => 'shell/Job.php',
    'JobBuiltin' => 'builtin/JobBuiltin.php',
    'JobLegacy' => 'shell/JobLegacy.php',
    'JoinedWordOrNumberSymbol' => 'parser/parser/JoinedWordOrNumberSymbol.php',
    'JsonContainer' => 'pipe/type/JsonContainer.php',
    'JsonLengthPrefixedEndpointTestCase' => '__tests__/JsonLengthPrefixedEndpointTestCase.php',
    'LaunchableInterface' => 'shell/LaunchableInterface.php',
    'LetterToken' => 'parser/lexer/DigitToken.php',
    'Lexer' => 'parser/Lexer.php',
    'NativeFDTestCase' => '__tests__/NativeFDTestCase.php',
    'NativeLaunchable' => 'shell/launchable/NativeLaunchable.php',
    'NativePipeClosedException' => 'pipe/NativePipeClosedException.php',
    'NewlineSeperatedEndpointTestCase' => '__tests__/NewlineSeperatedEndpointTestCase.php',
    'Node' => 'parser/state/Node.php',
    'NullSeperatedEndpointTestCase' => '__tests__/NullSeperatedEndpointTestCase.php',
    'NumberSymbol' => 'parser/parser/NumberSymbol.php',
    'OmniAppLaunchable' => 'shell/launchable/OmniAppLaunchable.php',
    'OmniTrace' => 'utils/utils.php',
    'OutboundFDStream' => 'pipe/type/OutboundFDStream.php',
    'OutboundStream' => 'pipe/OutboundStream.php',
    'PHPSerializationEndpointTestCase' => '__tests__/PHPSerializationEndpointTestCase.php',
    'ParseSyntaxTestCase' => '__tests__/ParseSyntaxTestCase.php',
    'Parser' => 'parser/Parser.php',
    'Pipe' => 'pipe/Pipe.php',
    'PipeCallVisitor' => 'shell/visitor/PipeCallVisitor.php',
    'PipeControllerTestCase' => '__tests__/PipeControllerTestCase.php',
    'PipelineVisitor' => 'shell/visitor/PipelineVisitor.php',
    'Process' => 'shell/Process.php',
    'ProcessIDWrapper' => 'shell/ProcessIDWrapper.php',
    'ProcessInterface' => 'shell/ProcessInterface.php',
    'ProcessLegacy' => 'shell/ProcessLegacy.php',
    'RootSymbol' => 'parser/parser/RootSymbol.php',
    'RootVisitor' => 'shell/visitor/RootVisitor.php',
    'Shell' => 'shell/Shell.php',
    'SingleQuotedVisitor' => 'shell/visitor/SingleQuotedVisitor.php',
    'StatementVisitor' => 'shell/visitor/StatementVisitor.php',
    'Symbol' => 'parser/parser/Symbol.php',
    'Token' => 'parser/lexer/Token.php',
    'TypeConverter' => 'pipe/TypeConverter.php',
    'UnderscoreToken' => 'parser/lexer/UnderscoreToken.php',
    'VariableVisitor' => 'shell/visitor/VariableVisitor.php',
    'Visitor' => 'shell/visitor/Visitor.php',
    'WhitespaceToken' => 'parser/lexer/WhitespaceToken.php',
    'WordOrNumberSymbol' => 'parser/parser/WordOrNumberSymbol.php',
    'WordSymbol' => 'parser/parser/WordSymbol.php',
  ),
  'function' => array(
    'omni_exit' => 'utils/utils.php',
    'omni_trace' => 'utils/utils.php',
  ),
  'xmap' => array(
    'ArgumentsVisitor' => 'Visitor',
    'AssignmentVisitor' => 'Visitor',
    'Builtin' => 'Phobject',
    'BuiltinLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'ByteStreamEndpointTestCase' => 'EndpointTestCase',
    'CommandVisitor' => 'Visitor',
    'DigitToken' => 'Token',
    'DoubleQuotedVisitor' => 'Visitor',
    'Endpoint' => 'Phobject',
    'EndpointTestCase' => 'PhutilTestCase',
    'Expected' => 'Phobject',
    'FragmentVisitor' => 'Visitor',
    'FragmentsVisitor' => 'Visitor',
    'InboundFDStream' => 'InboundStream',
    'InboundStream' => 'Phobject',
    'InteractiveTTYEditline' => 'Phobject',
    'InteractiveTTYManual' => 'Phobject',
    'InteractiveTTYNCursesPartial' => 'Phobject',
    'Job' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'JobBuiltin' => 'Builtin',
    'JobLegacy' => 'Phobject',
    'JoinedWordOrNumberSymbol' => 'Symbol',
    'JsonContainer' => 'Phobject',
    'JsonLengthPrefixedEndpointTestCase' => 'EndpointTestCase',
    'LetterToken' => 'Token',
    'Lexer' => 'Phobject',
    'NativeFDTestCase' => 'PhutilTestCase',
    'NativeLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'NativePipeClosedException' => 'Exception',
    'NewlineSeperatedEndpointTestCase' => 'EndpointTestCase',
    'Node' => 'Phobject',
    'NullSeperatedEndpointTestCase' => 'EndpointTestCase',
    'NumberSymbol' => 'Symbol',
    'OmniAppLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'OmniTrace' => 'Phobject',
    'OutboundFDStream' => 'OutboundStream',
    'OutboundStream' => 'Phobject',
    'PHPSerializationEndpointTestCase' => 'EndpointTestCase',
    'ParseSyntaxTestCase' => 'PhutilTestCase',
    'Parser' => 'Phobject',
    'Pipe' => 'Phobject',
    'PipeCallVisitor' => 'Visitor',
    'PipeControllerTestCase' => 'PhutilTestCase',
    'PipelineVisitor' => 'Visitor',
    'Process' => array(
      'Phobject',
      'LaunchableInterface',
      'ProcessInterface',
    ),
    'ProcessIDWrapper' => array(
      'Phobject',
      'ProcessInterface',
    ),
    'ProcessLegacy' => 'Phobject',
    'RootSymbol' => 'Symbol',
    'RootVisitor' => 'Visitor',
    'Shell' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'SingleQuotedVisitor' => 'Visitor',
    'StatementVisitor' => 'Visitor',
    'Symbol' => 'Phobject',
    'Token' => 'Phobject',
    'TypeConverter' => 'Phobject',
    'UnderscoreToken' => 'Token',
    'VariableVisitor' => 'Visitor',
    'WhitespaceToken' => 'Token',
    'WordOrNumberSymbol' => 'Symbol',
    'WordSymbol' => 'Symbol',
  ),
));
