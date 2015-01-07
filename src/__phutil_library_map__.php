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
    'AccessVisitor' => 'shell/visitor/AccessVisitor.php',
    'ArgumentsVisitor' => 'shell/visitor/ArgumentsVisitor.php',
    'ArrayContainer' => 'utils/ArrayContainer.php',
    'ArrayDeclVisitor' => 'shell/visitor/ArrayDeclVisitor.php',
    'ArrayDefVisitor' => 'shell/visitor/ArrayDefVisitor.php',
    'ArrayElementVisitor' => 'shell/visitor/ArrayElementVisitor.php',
    'AssignmentVisitor' => 'shell/visitor/AssignmentVisitor.php',
    'BaseEndpoint' => 'pipe/BaseEndpoint.php',
    'Builtin' => 'builtin/Builtin.php',
    'BuiltinExitCodeInterface' => 'shell/BuiltinExitCodeInterface.php',
    'BuiltinLaunchable' => 'shell/launchable/BuiltinLaunchable.php',
    'ByteStreamEndpointTestCase' => '__tests__/ByteStreamEndpointTestCase.php',
    'ChangeDirectoryBuiltin' => 'builtin/ChangeDirectoryBuiltin.php',
    'CommandVisitor' => 'shell/visitor/CommandVisitor.php',
    'DoubleQuotedVisitor' => 'shell/visitor/DoubleQuotedVisitor.php',
    'EIOWhileReadingStdinException' => 'pipe/EIOWhileReadingStdinException.php',
    'EchoBuiltin' => 'builtin/EchoBuiltin.php',
    'Endpoint' => 'pipe/Endpoint.php',
    'EndpointTestCase' => '__tests__/EndpointTestCase.php',
    'EvaluationWouldCauseSideEffectException' => 'shell/visitor/EvaluationWouldCauseSideEffectException.php',
    'ExitBuiltin' => 'builtin/ExitBuiltin.php',
    'ExpressionVisitor' => 'shell/visitor/ExpressionVisitor.php',
    'FileDescriptorManager' => 'utils/FileDescriptorManager.php',
    'FileSuggestionProvider' => 'suggestion/FileSuggestionProvider.php',
    'FixedPipe' => 'pipe/FixedPipe.php',
    'ForeachVisitor' => 'shell/visitor/ForeachVisitor.php',
    'FragmentVisitor' => 'shell/visitor/FragmentVisitor.php',
    'FragmentsVisitor' => 'shell/visitor/FragmentsVisitor.php',
    'FunctionLaunchable' => 'shell/launchable/FunctionLaunchable.php',
    'FunctionVisitor' => 'shell/visitor/FunctionVisitor.php',
    'FunctionalTestCase' => '__tests__/FunctionalTestCase.php',
    'FuturesBuiltin' => 'builtin/FuturesBuiltin.php',
    'HasTerminalModesInterface' => 'shell/HasTerminalModesInterface.php',
    'HttpServeBuiltin' => 'builtin/HttpServeBuiltin.php',
    'IfVisitor' => 'shell/visitor/IfVisitor.php',
    'InProcessEndpoint' => 'pipe/inprocess/InProcessEndpoint.php',
    'InProcessPipe' => 'pipe/inprocess/InProcessPipe.php',
    'InteractiveTTYEditline' => 'shell/interactive/InteractiveTTYEditline.php',
    'InvocationVisitor' => 'shell/visitor/InvocationVisitor.php',
    'Job' => 'shell/Job.php',
    'JobBuiltin' => 'builtin/JobBuiltin.php',
    'JobLegacy' => 'shell/JobLegacy.php',
    'JsonLengthPrefixedEndpointTestCase' => '__tests__/JsonLengthPrefixedEndpointTestCase.php',
    'KeyValueVisitor' => 'shell/visitor/KeyValueVisitor.php',
    'KeyValuesVisitor' => 'shell/visitor/KeyValuesVisitor.php',
    'LaunchableInterface' => 'shell/LaunchableInterface.php',
    'ListDirectoryBuiltin' => 'builtin/ListDirectoryBuiltin.php',
    'MethodCallReference' => 'shell/MethodCallReference.php',
    'NativeFDTestCase' => '__tests__/NativeFDTestCase.php',
    'NativeLaunchable' => 'shell/launchable/NativeLaunchable.php',
    'NativePipeClosedException' => 'pipe/NativePipeClosedException.php',
    'NewBuiltin' => 'builtin/NewBuiltin.php',
    'NewlineSeperatedEndpointTestCase' => '__tests__/NewlineSeperatedEndpointTestCase.php',
    'NullSeperatedEndpointTestCase' => '__tests__/NullSeperatedEndpointTestCase.php',
    'NumberVisitor' => 'shell/visitor/NumberVisitor.php',
    'OmniAppLaunchable' => 'shell/launchable/OmniAppLaunchable.php',
    'OmniBundler' => 'bundle/OmniBundler.php',
    'OmniFunction' => 'utils/OmniFunction.php',
    'OmniTrace' => 'utils/utils.php',
    'PHPSerializationEndpointTestCase' => '__tests__/PHPSerializationEndpointTestCase.php',
    'PHPVisitor' => 'shell/visitor/PHPVisitor.php',
    'ParseSyntaxTestCase' => '__tests__/ParseSyntaxTestCase.php',
    'Pipe' => 'pipe/Pipe.php',
    'PipeBuiltin' => 'builtin/PipeBuiltin.php',
    'PipeControllerTestCase' => '__tests__/PipeControllerTestCase.php',
    'PipeDefaults' => 'pipe/PipeDefaults.php',
    'PipeInterface' => 'pipe/PipeInterface.php',
    'PipelineVisitor' => 'shell/visitor/PipelineVisitor.php',
    'Process' => 'shell/Process.php',
    'ProcessAwareException' => 'pipe/ProcessAwareException.php',
    'ProcessIDWrapper' => 'shell/ProcessIDWrapper.php',
    'ProcessInterface' => 'shell/ProcessInterface.php',
    'ProcessLegacy' => 'shell/ProcessLegacy.php',
    'ReturnFlowControlException' => 'shell/visitor/flowcontrol/ReturnFlowControlException.php',
    'ReturnVisitor' => 'shell/visitor/ReturnVisitor.php',
    'Shell' => 'shell/Shell.php',
    'SingleQuotedVisitor' => 'shell/visitor/SingleQuotedVisitor.php',
    'SocketEndpoint' => 'pipe/SocketEndpoint.php',
    'StatementNotTerminatedException' => 'shell/StatementNotTerminatedException.php',
    'StatementVisitor' => 'shell/visitor/StatementVisitor.php',
    'StatementsVisitor' => 'shell/visitor/StatementsVisitor.php',
    'StructuredFile' => 'structured/StructuredFile.php',
    'SuggestionEngine' => 'suggestion/SuggestionEngine.php',
    'SuggestionProvider' => 'suggestion/SuggestionProvider.php',
    'TypeConverter' => 'pipe/TypeConverter.php',
    'UserFriendlyFormatter' => 'pipe/UserFriendlyFormatter.php',
    'VariableManager' => 'shell/VariableManager.php',
    'VariableVisitor' => 'shell/visitor/VariableVisitor.php',
    'Visitor' => 'shell/visitor/Visitor.php',
    'WebSocketEndpoint' => 'pipe/WebSocketEndpoint.php',
    'WhileVisitor' => 'shell/visitor/WhileVisitor.php',
  ),
  'function' => array(
    'omni_exit' => 'utils/utils.php',
    'omni_trace' => 'utils/utils.php',
  ),
  'xmap' => array(
    'AccessVisitor' => 'Visitor',
    'ArgumentsVisitor' => 'Visitor',
    'ArrayContainer' => 'Phobject',
    'ArrayDeclVisitor' => 'Visitor',
    'ArrayDefVisitor' => 'Visitor',
    'ArrayElementVisitor' => 'Visitor',
    'AssignmentVisitor' => 'Visitor',
    'BaseEndpoint' => 'Phobject',
    'Builtin' => 'Phobject',
    'BuiltinLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
      'BuiltinExitCodeInterface',
    ),
    'ByteStreamEndpointTestCase' => 'EndpointTestCase',
    'ChangeDirectoryBuiltin' => 'Builtin',
    'CommandVisitor' => 'Visitor',
    'DoubleQuotedVisitor' => 'Visitor',
    'EIOWhileReadingStdinException' => 'Exception',
    'EchoBuiltin' => 'Builtin',
    'Endpoint' => 'BaseEndpoint',
    'EndpointTestCase' => 'PhutilTestCase',
    'EvaluationWouldCauseSideEffectException' => 'Exception',
    'ExitBuiltin' => 'Builtin',
    'ExpressionVisitor' => 'Visitor',
    'FileDescriptorManager' => 'Phobject',
    'FileSuggestionProvider' => 'SuggestionProvider',
    'FixedPipe' => 'Pipe',
    'ForeachVisitor' => 'Visitor',
    'FragmentVisitor' => 'Visitor',
    'FragmentsVisitor' => 'Visitor',
    'FunctionLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'FunctionVisitor' => 'Visitor',
    'FunctionalTestCase' => 'PhutilTestCase',
    'FuturesBuiltin' => 'Builtin',
    'HttpServeBuiltin' => 'Builtin',
    'IfVisitor' => 'Visitor',
    'InProcessEndpoint' => 'Phobject',
    'InProcessPipe' => array(
      'Phobject',
      'PipeInterface',
    ),
    'InteractiveTTYEditline' => 'Phobject',
    'InvocationVisitor' => 'Visitor',
    'Job' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'JobBuiltin' => 'Builtin',
    'JobLegacy' => 'Phobject',
    'JsonLengthPrefixedEndpointTestCase' => 'EndpointTestCase',
    'KeyValueVisitor' => 'Visitor',
    'KeyValuesVisitor' => 'Visitor',
    'ListDirectoryBuiltin' => 'Builtin',
    'MethodCallReference' => 'Phobject',
    'NativeFDTestCase' => 'PhutilTestCase',
    'NativeLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'NativePipeClosedException' => 'Exception',
    'NewBuiltin' => 'Builtin',
    'NewlineSeperatedEndpointTestCase' => 'EndpointTestCase',
    'NullSeperatedEndpointTestCase' => 'EndpointTestCase',
    'NumberVisitor' => 'Visitor',
    'OmniAppLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'OmniBundler' => 'Phobject',
    'OmniFunction' => 'Phobject',
    'OmniTrace' => 'Phobject',
    'PHPSerializationEndpointTestCase' => 'EndpointTestCase',
    'PHPVisitor' => 'Visitor',
    'ParseSyntaxTestCase' => 'PhutilTestCase',
    'Pipe' => array(
      'Phobject',
      'PipeInterface',
    ),
    'PipeBuiltin' => 'Builtin',
    'PipeControllerTestCase' => 'PhutilTestCase',
    'PipelineVisitor' => 'Visitor',
    'Process' => array(
      'Phobject',
      'LaunchableInterface',
      'ProcessInterface',
    ),
    'ProcessAwareException' => 'Exception',
    'ProcessIDWrapper' => array(
      'Phobject',
      'ProcessInterface',
    ),
    'ProcessLegacy' => 'Phobject',
    'ReturnFlowControlException' => 'Exception',
    'ReturnVisitor' => 'Visitor',
    'Shell' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'SingleQuotedVisitor' => 'Visitor',
    'SocketEndpoint' => 'BaseEndpoint',
    'StatementNotTerminatedException' => 'Exception',
    'StatementVisitor' => 'Visitor',
    'StatementsVisitor' => 'Visitor',
    'StructuredFile' => 'Phobject',
    'SuggestionEngine' => 'Phobject',
    'SuggestionProvider' => 'Phobject',
    'TypeConverter' => 'Phobject',
    'UserFriendlyFormatter' => 'Phobject',
    'VariableManager' => 'Phobject',
    'VariableVisitor' => 'Visitor',
    'WebSocketEndpoint' => 'BaseEndpoint',
    'WhileVisitor' => 'Visitor',
  ),
));
