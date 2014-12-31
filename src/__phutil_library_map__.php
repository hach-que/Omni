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
    'AssignmentVisitor' => 'shell/visitor/AssignmentVisitor.php',
    'Builtin' => 'builtin/Builtin.php',
    'BuiltinLaunchable' => 'shell/launchable/BuiltinLaunchable.php',
    'ByteStreamEndpointTestCase' => '__tests__/ByteStreamEndpointTestCase.php',
    'ChangeDirectoryBuiltin' => 'builtin/ChangeDirectoryBuiltin.php',
    'CommandVisitor' => 'shell/visitor/CommandVisitor.php',
    'DoubleQuotedVisitor' => 'shell/visitor/DoubleQuotedVisitor.php',
    'EIOWhileReadingStdinException' => 'pipe/EIOWhileReadingStdinException.php',
    'EchoBuiltin' => 'builtin/EchoBuiltin.php',
    'Endpoint' => 'pipe/Endpoint.php',
    'EndpointTestCase' => '__tests__/EndpointTestCase.php',
    'ExitBuiltin' => 'builtin/ExitBuiltin.php',
    'ExpressionVisitor' => 'shell/visitor/ExpressionVisitor.php',
    'FileDescriptorManager' => 'utils/FileDescriptorManager.php',
    'FixedPipe' => 'pipe/FixedPipe.php',
    'FragmentVisitor' => 'shell/visitor/FragmentVisitor.php',
    'FragmentsVisitor' => 'shell/visitor/FragmentsVisitor.php',
    'FunctionalTestCase' => '__tests__/FunctionalTestCase.php',
    'HasTerminalModesInterface' => 'shell/HasTerminalModesInterface.php',
    'IfVisitor' => 'shell/visitor/IfVisitor.php',
    'InProcessEndpoint' => 'pipe/inprocess/InProcessEndpoint.php',
    'InProcessPipe' => 'pipe/inprocess/InProcessPipe.php',
    'InteractiveTTYEditline' => 'interactive/InteractiveTTYEditline.php',
    'InteractiveTTYManual' => 'interactive/InteractiveTTYManual.php',
    'InteractiveTTYNCursesPartial' => 'interactive/InteractiveTTYNCursesPartial.php',
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
    'OmniTrace' => 'utils/utils.php',
    'PHPSerializationEndpointTestCase' => '__tests__/PHPSerializationEndpointTestCase.php',
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
    'Shell' => 'shell/Shell.php',
    'SingleQuotedVisitor' => 'shell/visitor/SingleQuotedVisitor.php',
    'StatementVisitor' => 'shell/visitor/StatementVisitor.php',
    'StatementsVisitor' => 'shell/visitor/StatementsVisitor.php',
    'StructuredFile' => 'structured/StructuredFile.php',
    'TypeConverter' => 'pipe/TypeConverter.php',
    'UserFriendlyFormatter' => 'pipe/UserFriendlyFormatter.php',
    'VariableVisitor' => 'shell/visitor/VariableVisitor.php',
    'Visitor' => 'shell/visitor/Visitor.php',
  ),
  'function' => array(
    'omni_exit' => 'utils/utils.php',
    'omni_trace' => 'utils/utils.php',
  ),
  'xmap' => array(
    'AccessVisitor' => 'Visitor',
    'ArgumentsVisitor' => 'Visitor',
    'AssignmentVisitor' => 'Visitor',
    'Builtin' => 'Phobject',
    'BuiltinLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'ByteStreamEndpointTestCase' => 'EndpointTestCase',
    'ChangeDirectoryBuiltin' => 'Builtin',
    'CommandVisitor' => 'Visitor',
    'DoubleQuotedVisitor' => 'Visitor',
    'EIOWhileReadingStdinException' => 'Exception',
    'EchoBuiltin' => 'Builtin',
    'Endpoint' => 'Phobject',
    'EndpointTestCase' => 'PhutilTestCase',
    'ExitBuiltin' => 'Builtin',
    'ExpressionVisitor' => 'Visitor',
    'FileDescriptorManager' => 'Phobject',
    'FixedPipe' => 'Pipe',
    'FragmentVisitor' => 'Visitor',
    'FragmentsVisitor' => 'Visitor',
    'FunctionalTestCase' => 'PhutilTestCase',
    'IfVisitor' => 'Visitor',
    'InProcessEndpoint' => 'Phobject',
    'InProcessPipe' => array(
      'Phobject',
      'PipeInterface',
    ),
    'InteractiveTTYEditline' => 'Phobject',
    'InteractiveTTYManual' => 'Phobject',
    'InteractiveTTYNCursesPartial' => 'Phobject',
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
    'OmniTrace' => 'Phobject',
    'PHPSerializationEndpointTestCase' => 'EndpointTestCase',
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
    'Shell' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'SingleQuotedVisitor' => 'Visitor',
    'StatementVisitor' => 'Visitor',
    'StatementsVisitor' => 'Visitor',
    'StructuredFile' => 'Phobject',
    'TypeConverter' => 'Phobject',
    'UserFriendlyFormatter' => 'Phobject',
    'VariableVisitor' => 'Visitor',
  ),
));
