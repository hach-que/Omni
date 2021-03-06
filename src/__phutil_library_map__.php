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
    'AddVisitor' => 'shell/visitor/AddVisitor.php',
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
    'BytesContainer' => 'utils/BytesContainer.php',
    'Chain' => 'shell/Chain.php',
    'ChainExecVisitor' => 'shell/visitor/ChainExecVisitor.php',
    'ChainVisitor' => 'shell/visitor/ChainVisitor.php',
    'ChangeDirectoryBuiltin' => 'builtin/ChangeDirectoryBuiltin.php',
    'CommandVisitor' => 'shell/visitor/CommandVisitor.php',
    'DivideVisitor' => 'shell/visitor/DivideVisitor.php',
    'DoubleQuotedVisitor' => 'shell/visitor/DoubleQuotedVisitor.php',
    'EIOWhileReadingStdinException' => 'pipe/EIOWhileReadingStdinException.php',
    'EchoBuiltin' => 'builtin/EchoBuiltin.php',
    'Endpoint' => 'pipe/Endpoint.php',
    'EndpointTestCase' => '__tests__/EndpointTestCase.php',
    'EqualsVisitor' => 'shell/visitor/EqualsVisitor.php',
    'EvaluationWouldCauseSideEffectException' => 'shell/visitor/EvaluationWouldCauseSideEffectException.php',
    'ExecutableSuggestionProvider' => 'suggestion/ExecutableSuggestionProvider.php',
    'ExitBuiltin' => 'builtin/ExitBuiltin.php',
    'ExpressionExpander' => 'shell/ExpressionExpander.php',
    'ExpressionVisitor' => 'shell/visitor/ExpressionVisitor.php',
    'ExtensionProvider' => 'extension/ExtensionProvider.php',
    'FileDescriptorManager' => 'utils/FileDescriptorManager.php',
    'FileSuggestionProvider' => 'suggestion/FileSuggestionProvider.php',
    'FixedPipe' => 'pipe/FixedPipe.php',
    'ForVisitor' => 'shell/visitor/ForVisitor.php',
    'ForeachVisitor' => 'shell/visitor/ForeachVisitor.php',
    'FragmentVisitor' => 'shell/visitor/FragmentVisitor.php',
    'FragmentsVisitor' => 'shell/visitor/FragmentsVisitor.php',
    'FunctionLaunchable' => 'shell/launchable/FunctionLaunchable.php',
    'FunctionVisitor' => 'shell/visitor/FunctionVisitor.php',
    'FunctionalTestCase' => '__tests__/FunctionalTestCase.php',
    'FuturesBuiltin' => 'builtin/FuturesBuiltin.php',
    'GitBranchSuggestionProvider' => 'suggestion/GitBranchSuggestionProvider.php',
    'GreaterThanEqualsVisitor' => 'shell/visitor/GreaterThanEqualsVisitor.php',
    'GreaterThanVisitor' => 'shell/visitor/GreaterThanVisitor.php',
    'HasTerminalModesInterface' => 'shell/HasTerminalModesInterface.php',
    'HttpServeBuiltin' => 'builtin/HttpServeBuiltin.php',
    'IfVisitor' => 'shell/visitor/IfVisitor.php',
    'InProcessEndpoint' => 'pipe/inprocess/InProcessEndpoint.php',
    'InProcessPipe' => 'pipe/inprocess/InProcessPipe.php',
    'InteractiveTTYEditline' => 'shell/interactive/InteractiveTTYEditline.php',
    'InvocationVisitor' => 'shell/visitor/InvocationVisitor.php',
    'IterBuiltin' => 'builtin/IterBuiltin.php',
    'Job' => 'shell/Job.php',
    'JobBuiltin' => 'builtin/JobBuiltin.php',
    'JobLegacy' => 'shell/JobLegacy.php',
    'JsonLengthPrefixedEndpointTestCase' => '__tests__/JsonLengthPrefixedEndpointTestCase.php',
    'KeyValueVisitor' => 'shell/visitor/KeyValueVisitor.php',
    'KeyValuesVisitor' => 'shell/visitor/KeyValuesVisitor.php',
    'LaunchableInterface' => 'shell/LaunchableInterface.php',
    'LessThanEqualsVisitor' => 'shell/visitor/LessThanEqualsVisitor.php',
    'LessThanVisitor' => 'shell/visitor/LessThanVisitor.php',
    'ListDirectoryBuiltin' => 'builtin/ListDirectoryBuiltin.php',
    'MetaDirectoryStructuredFile' => 'vfs/structured/MetaDirectoryStructuredFile.php',
    'MethodCallReference' => 'shell/MethodCallReference.php',
    'MinusVisitor' => 'shell/visitor/MinusVisitor.php',
    'MultiplyVisitor' => 'shell/visitor/MultiplyVisitor.php',
    'NativeFDTestCase' => '__tests__/NativeFDTestCase.php',
    'NativeLaunchable' => 'shell/launchable/NativeLaunchable.php',
    'NativePipeClosedException' => 'pipe/NativePipeClosedException.php',
    'NativePipeNonblockingWriteNotReadyException' => 'pipe/NativePipeNonblockingWriteNotReadyException.php',
    'NewBuiltin' => 'builtin/NewBuiltin.php',
    'NewlineSeperatedEndpointTestCase' => '__tests__/NewlineSeperatedEndpointTestCase.php',
    'NonblockingWriteBuffer' => 'pipe/NonblockingWriteBuffer.php',
    'NotEqualsVisitor' => 'shell/visitor/NotEqualsVisitor.php',
    'NotVisitor' => 'shell/visitor/NotVisitor.php',
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
    'Pipeline' => 'shell/Pipeline.php',
    'PipelineOrChainInterface' => 'shell/PipelineOrChainInterface.php',
    'PipelineVisitor' => 'shell/visitor/PipelineVisitor.php',
    'Process' => 'shell/Process.php',
    'ProcessAwareException' => 'pipe/ProcessAwareException.php',
    'ProcessIDWrapper' => 'shell/ProcessIDWrapper.php',
    'ProcessInterface' => 'shell/ProcessInterface.php',
    'ProcessLegacy' => 'shell/ProcessLegacy.php',
    'RealStructuredFile' => 'vfs/structured/RealStructuredFile.php',
    'RealVirtualFSProvider' => 'vfs/provider/RealVirtualFSProvider.php',
    'ReturnFlowControlException' => 'shell/visitor/flowcontrol/ReturnFlowControlException.php',
    'ReturnVisitor' => 'shell/visitor/ReturnVisitor.php',
    'Shell' => 'shell/Shell.php',
    'SingleQuotedVisitor' => 'shell/visitor/SingleQuotedVisitor.php',
    'SocketEndpoint' => 'pipe/SocketEndpoint.php',
    'SortBuiltin' => 'builtin/SortBuiltin.php',
    'StatementNotTerminatedException' => 'shell/StatementNotTerminatedException.php',
    'StatementVisitor' => 'shell/visitor/StatementVisitor.php',
    'StatementsVisitor' => 'shell/visitor/StatementsVisitor.php',
    'StringOperator' => 'utils/StringOperator.php',
    'StructuredFileCustomInterface' => 'vfs/structured/StructuredFileCustomInterface.php',
    'StructuredFileInterface' => 'vfs/structured/StructuredFileInterface.php',
    'StructuredFilePermissionsInterface' => 'vfs/structured/StructuredFilePermissionsInterface.php',
    'StructuredFileSizeInterface' => 'vfs/structured/StructuredFileSizeInterface.php',
    'StructuredFileTimeInterface' => 'vfs/structured/StructuredFileTimeInterface.php',
    'SuggestionEngine' => 'suggestion/SuggestionEngine.php',
    'SuggestionProvider' => 'suggestion/SuggestionProvider.php',
    'SuggestionsTestCase' => '__tests__/SuggestionsTestCase.php',
    'SystemdUnitStructuredFile' => 'vfs/structured/systemd/SystemdUnitStructuredFile.php',
    'SystemdVirtualFSProvider' => 'vfs/provider/SystemdVirtualFSProvider.php',
    'TestStructuredFile' => 'vfs/structured/test/TestStructuredFile.php',
    'TestVirtualFSProvider' => 'vfs/provider/TestVirtualFSProvider.php',
    'TypeConverter' => 'pipe/TypeConverter.php',
    'UserFriendlyFormatter' => 'pipe/UserFriendlyFormatter.php',
    'VFSProviderStructuredFile' => 'vfs/structured/VFSProviderStructuredFile.php',
    'VFSVirtualFSProvider' => 'vfs/provider/VFSVirtualFSProvider.php',
    'VariableManager' => 'shell/VariableManager.php',
    'VariableVisitor' => 'shell/visitor/VariableVisitor.php',
    'VirtualFS' => 'vfs/VirtualFS.php',
    'VirtualFSProvider' => 'vfs/provider/VirtualFSProvider.php',
    'VirtualFSSession' => 'vfs/VirtualFSSession.php',
    'Visitor' => 'shell/visitor/Visitor.php',
    'WebSocketEndpoint' => 'pipe/WebSocketEndpoint.php',
    'WhileVisitor' => 'shell/visitor/WhileVisitor.php',
    'ZypperManagerStructuredFile' => 'vfs/structured/zypper/ZypperManagerStructuredFile.php',
    'ZypperPackageStructuredFile' => 'vfs/structured/zypper/ZypperPackageStructuredFile.php',
    'ZypperRepositoryStructuredFile' => 'vfs/structured/zypper/ZypperRepositoryStructuredFile.php',
    'ZypperVirtualFSProvider' => 'vfs/provider/ZypperVirtualFSProvider.php',
  ),
  'function' => array(
    'omni_exit' => 'utils/utils.php',
    'omni_trace' => 'utils/utils.php',
  ),
  'xmap' => array(
    'AccessVisitor' => 'Visitor',
    'AddVisitor' => 'Visitor',
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
    'BytesContainer' => 'Phobject',
    'Chain' => array(
      'Phobject',
      'PipelineOrChainInterface',
    ),
    'ChainExecVisitor' => 'Visitor',
    'ChainVisitor' => 'Visitor',
    'ChangeDirectoryBuiltin' => 'Builtin',
    'CommandVisitor' => 'Visitor',
    'DivideVisitor' => 'Visitor',
    'DoubleQuotedVisitor' => 'Visitor',
    'EIOWhileReadingStdinException' => 'Exception',
    'EchoBuiltin' => 'Builtin',
    'Endpoint' => 'BaseEndpoint',
    'EndpointTestCase' => 'PhutilTestCase',
    'EqualsVisitor' => 'Visitor',
    'EvaluationWouldCauseSideEffectException' => 'Exception',
    'ExecutableSuggestionProvider' => 'SuggestionProvider',
    'ExitBuiltin' => 'Builtin',
    'ExpressionExpander' => 'Phobject',
    'ExpressionVisitor' => 'Visitor',
    'ExtensionProvider' => 'Phobject',
    'FileDescriptorManager' => 'Phobject',
    'FileSuggestionProvider' => 'SuggestionProvider',
    'FixedPipe' => 'Pipe',
    'ForVisitor' => 'Visitor',
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
    'GitBranchSuggestionProvider' => 'SuggestionProvider',
    'GreaterThanEqualsVisitor' => 'Visitor',
    'GreaterThanVisitor' => 'Visitor',
    'HttpServeBuiltin' => 'Builtin',
    'IfVisitor' => 'Visitor',
    'InProcessEndpoint' => 'Phobject',
    'InProcessPipe' => array(
      'Phobject',
      'PipeInterface',
    ),
    'InteractiveTTYEditline' => 'Phobject',
    'InvocationVisitor' => 'Visitor',
    'IterBuiltin' => 'Builtin',
    'Job' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'JobBuiltin' => 'Builtin',
    'JobLegacy' => 'Phobject',
    'JsonLengthPrefixedEndpointTestCase' => 'EndpointTestCase',
    'KeyValueVisitor' => 'Visitor',
    'KeyValuesVisitor' => 'Visitor',
    'LessThanEqualsVisitor' => 'Visitor',
    'LessThanVisitor' => 'Visitor',
    'ListDirectoryBuiltin' => 'Builtin',
    'MetaDirectoryStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'MethodCallReference' => 'Phobject',
    'MinusVisitor' => 'Visitor',
    'MultiplyVisitor' => 'Visitor',
    'NativeFDTestCase' => 'PhutilTestCase',
    'NativeLaunchable' => array(
      'Phobject',
      'LaunchableInterface',
    ),
    'NativePipeClosedException' => 'Exception',
    'NativePipeNonblockingWriteNotReadyException' => 'Exception',
    'NewBuiltin' => 'Builtin',
    'NewlineSeperatedEndpointTestCase' => 'EndpointTestCase',
    'NonblockingWriteBuffer' => 'Phobject',
    'NotEqualsVisitor' => 'Visitor',
    'NotVisitor' => 'Visitor',
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
    'Pipeline' => array(
      'Phobject',
      'PipelineOrChainInterface',
    ),
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
    'RealStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
      'StructuredFileTimeInterface',
      'StructuredFilePermissionsInterface',
      'StructuredFileSizeInterface',
    ),
    'RealVirtualFSProvider' => 'VirtualFSProvider',
    'ReturnFlowControlException' => 'Exception',
    'ReturnVisitor' => 'Visitor',
    'Shell' => array(
      'Phobject',
      'HasTerminalModesInterface',
    ),
    'SingleQuotedVisitor' => 'Visitor',
    'SocketEndpoint' => 'BaseEndpoint',
    'SortBuiltin' => 'Builtin',
    'StatementNotTerminatedException' => 'Exception',
    'StatementVisitor' => 'Visitor',
    'StatementsVisitor' => 'Visitor',
    'StringOperator' => 'Phobject',
    'SuggestionEngine' => 'Phobject',
    'SuggestionProvider' => 'Phobject',
    'SuggestionsTestCase' => 'PhutilTestCase',
    'SystemdUnitStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
      'StructuredFileCustomInterface',
    ),
    'SystemdVirtualFSProvider' => 'VirtualFSProvider',
    'TestStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'TestVirtualFSProvider' => 'VirtualFSProvider',
    'TypeConverter' => 'Phobject',
    'UserFriendlyFormatter' => 'Phobject',
    'VFSProviderStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'VFSVirtualFSProvider' => 'VirtualFSProvider',
    'VariableManager' => 'Phobject',
    'VariableVisitor' => 'Visitor',
    'VirtualFS' => 'Phobject',
    'VirtualFSProvider' => 'Phobject',
    'VirtualFSSession' => 'Phobject',
    'WebSocketEndpoint' => 'BaseEndpoint',
    'WhileVisitor' => 'Visitor',
    'ZypperManagerStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'ZypperPackageStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'ZypperRepositoryStructuredFile' => array(
      'Phobject',
      'StructuredFileInterface',
    ),
    'ZypperVirtualFSProvider' => 'VirtualFSProvider',
  ),
));
