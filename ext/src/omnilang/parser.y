%{

#define YYERROR_VERBOSE
#define YYDEBUG 1

#include <bstring.h>
#include <ast.h>

#define VALUE(a) (a).value
#define VALUE_NODE(a) (a).node
#define ORIGINAL_INIT(a) { (a).original = bfromcstr(""); }
#define ORIGINAL_APPEND(a, b) { if ((a).original == NULL) { (a).original = bfromcstr(""); } bconcat((a).original, (b).original); }
#define ORIGINAL_NODE_APPEND(a, b) { bconcat(((ast_node*)((a).node))->original, (b).original); }
#define ORIGINAL_NODE_NODE_APPEND(a, b) { ((ast_node*)((b).node))->position = blength(((ast_node*)((a).node))->original); bconcat(((ast_node*)((a).node))->original, ((ast_node*)((b).node))->original); }

// Root node for the AST.
ast_node* ast_root;

%}

%union
{
  struct bstring_with_original {
    bstring value;
    bstring original;
  } string;
  struct number_with_original {
    int value;
    bstring original;
  } number;
  struct token_with_original {
    enum yytokentype value;
    bstring original;
  } token;
  struct node_with_original {
    void* node;
  } node;
}

// Tokens that appear for both expressions and commands
%token <string> SINGLE_QUOTED DOUBLE_QUOTED
%token <string> KEYWORD_WHILE KEYWORD_DO KEYWORD_FOR KEYWORD_FOREACH KEYWORD_BREAK
%token <string> KEYWORD_CONTINUE KEYWORD_IF KEYWORD_ELSE KEYWORD_AS KEYWORD_RETURN
%token <string> VARIABLE
%token <token> END_PAREN BEGIN_BRACE END_BRACE END_SQUARE
%token <string> QUESTION_MARK

// Tokens that appear only for commands
%token <string> CMD_FRAGMENT CMD_MAP
%token <number> CMD_NUMBER
%token <token> CMD_DOLLAR CMD_AMPERSAND CMD_PIPE CMD_COLON
%token <token> CMD_WHITESPACE CMD_TERMINATING_NEWLINE
%token <token> CMD_BEGIN_COMMAND
%token <token> CMD_BEGIN_PAREN
%token <token> CMD_BEGIN_SQUARE
%token <token> CMD_SEMICOLON
%token <token> CMD_EQUALS

// Tokens that appear only for expressions
%token <string> PHP EXPR_FRAGMENT
%token <number> EXPR_NUMBER
%token <token> EXPR_DOLLAR EXPR_ACCESS EXPR_MAP
%token <token> EXPR_AMPERSAND EXPR_PIPE EXPR_EQUALS EXPR_COMMA EXPR_SEMICOLON
%token <token> EXPR_COLON EXPR_ADD EXPR_MINUS EXPR_MULTIPLY EXPR_DIVIDE
%token <token> EXPR_BEGIN_COMMAND
%token <token> EXPR_BEGIN_ARRAY
%token <token> EXPR_BEGIN_PAREN
%token <token> EXPR_BEGIN_SQUARE
%token <token> EXPR_END_SQUARE_BEGIN_COMMAND
%token <token> EXPR_TERMINATING_NEWLINE

// Miscellanous tokens
%token <token> UNTERMINATED_LEXING_BLOCK ERROR

// Nodes that appear only for commands
%type <token> cmd_terminator
%type <node> cmd_root
%type <node> cmd_statements
%type <node> cmd_statement
%type <node> cmd_pipeline
%type <node> cmd_fragment
%type <node> cmd_mapping
%type <node> cmd_assignment
%type <node> cmd_instruction
%type <node> cmd_command
%type <node> cmd_arguments
%type <node> cmd_fragments
%type <node> cmd_number
%type <string> cmd_keyword_as_string
%type <node> cmd_function_declaration
%type <string> cmd_fragment_or_variable
%type <token> cmd_optional_whitespace

// Nodes that appear only expression
%type <string> expr_key_name
%type <node> expr_key_values
%type <node> expr_function_declaration
%type <node> expr_fragments
%type <node> expr_fragment
%type <string> expr_fragment_or_variable
%type <node> expr_array_decl
%type <node> expr_array_def
%type <node> expr_array_element
%type <node> expr_number
%type <node> expr_comma_arguments
%type <node> expression
%type <node> expr_fragments_or_function_declaration
%type <string> expr_keyword_as_string

// Precedence rules
%nonassoc EXPR_PIPE CMD_PIPE
%nonassoc EXPR_AMPERSAND CMD_AMPERSAND
%nonassoc CMD_WHITESPACE
%nonassoc EXPR_COMMA
%left EXPR_ADD EXPR_MINUS
%left EXPR_MULTIPLY EXPR_DIVIDE
%left EXPR_BEGIN_PAREN
%left EXPR_ACCESS

%start cmd_root

%expect 0

%%
  
/* ---------- Rules that appear only in commands --------------------------- */

cmd_root:
  cmd_statements
  {
    ast_root = VALUE_NODE($1);
  } ;

cmd_statements:
  cmd_statement
  {
    VALUE_NODE($$) = ast_node_create(&node_type_root);
    
    if (VALUE_NODE($1) != NULL) {
      ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
      ORIGINAL_NODE_NODE_APPEND($$, $1);
    }
  } |
  cmd_statements cmd_terminator cmd_statement
  {
    ORIGINAL_NODE_APPEND($1, $2);
      
    if (VALUE_NODE($3) != NULL) {
      ast_node_append_child(VALUE_NODE($1), VALUE_NODE($3));
      ORIGINAL_NODE_NODE_APPEND($1, $3);
    }
  } ;

cmd_terminator:
  CMD_TERMINATING_NEWLINE |
  EXPR_TERMINATING_NEWLINE |
  CMD_SEMICOLON |
  EXPR_SEMICOLON ;
  
cmd_optional_whitespace:
  /* empty */             { $$.original = bfromcstr(""); } |
  CMD_WHITESPACE          { $$.original = bfromcstr(""); ORIGINAL_APPEND($$, $1); } |
  CMD_TERMINATING_NEWLINE { $$.original = bfromcstr(""); ORIGINAL_APPEND($$, $1); } ;
  
cmd_statement:
  cmd_pipeline CMD_AMPERSAND
  {
    VALUE_NODE($$) = VALUE_NODE($1);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(&pipeline_type_background));
    
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  cmd_pipeline
  {
    VALUE_NODE($$) = VALUE_NODE($1);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(&pipeline_type_foreground));
  } |
  cmd_assignment
  {
    $$ = $1;
  } |
  /* This rule changes to expression mode */
  CMD_COLON expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
  } |
  KEYWORD_IF cmd_optional_whitespace cmd_fragment cmd_optional_whitespace BEGIN_BRACE cmd_statements END_BRACE KEYWORD_ELSE BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_if);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($6));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($10));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_NODE_APPEND($$, $6);
    ORIGINAL_NODE_APPEND($$, $7);
    ORIGINAL_NODE_APPEND($$, $8);
    ORIGINAL_NODE_APPEND($$, $9);
    ORIGINAL_NODE_NODE_APPEND($$, $10);
    ORIGINAL_NODE_APPEND($$, $11);
  } |
  KEYWORD_IF cmd_optional_whitespace cmd_fragment cmd_optional_whitespace BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_if);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($6));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_NODE_APPEND($$, $6);
    ORIGINAL_NODE_APPEND($$, $7);
  } |
  KEYWORD_WHILE cmd_optional_whitespace cmd_fragment cmd_optional_whitespace BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_while);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($6));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_NODE_APPEND($$, $6);
    ORIGINAL_NODE_APPEND($$, $7);
  } |
  KEYWORD_DO cmd_optional_whitespace BEGIN_BRACE cmd_statements END_BRACE KEYWORD_WHILE cmd_optional_whitespace cmd_fragment
  {
    VALUE_NODE($$) = ast_node_create(&node_type_do);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($8));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($4));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
    ORIGINAL_NODE_APPEND($$, $7);
    ORIGINAL_NODE_NODE_APPEND($$, $8);
  } |
  KEYWORD_FOREACH cmd_optional_whitespace cmd_fragment cmd_optional_whitespace
    KEYWORD_AS cmd_optional_whitespace cmd_mapping 
    BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_foreach);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($7));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($9));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
    ORIGINAL_NODE_NODE_APPEND($$, $7);
    ORIGINAL_NODE_APPEND($$, $8);
    ORIGINAL_NODE_NODE_APPEND($$, $9);
    ORIGINAL_NODE_APPEND($$, $10);
  } |
  KEYWORD_RETURN CMD_WHITESPACE expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_return);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  /* empty */
  {
    VALUE_NODE($$) = NULL;
  };

cmd_mapping:
  CMD_DOLLAR VARIABLE cmd_optional_whitespace CMD_MAP cmd_optional_whitespace CMD_DOLLAR VARIABLE cmd_optional_whitespace
  {
    VALUE_NODE($$) = ast_node_create(&node_type_key_value);
  
    ast_node *key, *value;
    key = ast_node_create(&node_type_fragment);
    value = ast_node_create(&node_type_fragment);
    ast_node_set_string(key, bstrcpy(VALUE($2)));
    ast_node_set_string(value, bstrcpy(VALUE($7)));
    ast_node_append_child(VALUE_NODE($$), key);
    ast_node_append_child(VALUE_NODE($$), value);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
    ORIGINAL_NODE_APPEND($$, $7);
  } |
  CMD_DOLLAR VARIABLE cmd_optional_whitespace
  {
    VALUE_NODE($$) = ast_node_create(&node_type_key_value);
  
    ast_node *key, *value;
    key = ast_node_create(&node_type_fragment);
    ast_node_set_string(key, bstrcpy(VALUE($2)));
    ast_node_append_child(VALUE_NODE($$), key);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } ;

cmd_assignment:
  /* This rule changes to expression mode */
  CMD_COLON EXPR_DOLLAR VARIABLE EXPR_EQUALS expr_fragments_or_function_declaration
  {
    ast_node* variable;
    variable = ast_node_create(&node_type_variable);
    ast_node_set_string(variable, bstrcpy(VALUE($3)));
    
    VALUE_NODE($$) = ast_node_create(&node_type_assignment);
    ast_node_append_child(VALUE_NODE($$), variable);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($5));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_NODE_APPEND($$, $5);
  } |
  /* This rule changes to expression mode */
  CMD_COLON expression EXPR_ACCESS expression EXPR_EQUALS expr_fragments_or_function_declaration
  {
    VALUE_NODE($$) = ast_node_create(&node_type_access);
    ast_node_set_string(VALUE_NODE($$), bfromcstr("assign"));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($4));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($6));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_NODE_APPEND($$, $6);
  } |
  /* This rule changes to expression mode */
  CMD_COLON expression EXPR_ACCESS EXPR_BEGIN_SQUARE END_SQUARE EXPR_EQUALS expr_fragments_or_function_declaration
  {
    ast_node* fragment;
    fragment = ast_node_create(&node_type_fragment);
    ast_node_set_string(fragment, bfromcstr("[]"));
    
    VALUE_NODE($$) = ast_node_create(&node_type_access);
    ast_node_set_string(VALUE_NODE($$), bfromcstr("assign"));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    ast_node_append_child(VALUE_NODE($$), fragment);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($7));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
    ORIGINAL_NODE_NODE_APPEND($$, $7);
  } ;
  
cmd_pipeline:
  cmd_instruction
  {
    VALUE_NODE($$) = ast_node_create(&node_type_pipeline);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  cmd_pipeline CMD_PIPE cmd_instruction
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($1, $2);
    ORIGINAL_NODE_NODE_APPEND($1, $3);
  };
  
cmd_instruction:
  cmd_command
  {
    VALUE_NODE($$) = ast_node_create(&node_type_command);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  cmd_function_declaration
  {
    $$ = $1;
  } ;
  
cmd_command:
  cmd_arguments
  {
    $$ = $1;
  } ;

cmd_arguments:
  cmd_fragments
  {
    VALUE_NODE($$) = ast_node_create(&node_type_arguments);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  cmd_arguments CMD_WHITESPACE cmd_fragments
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  };

cmd_fragments:
  cmd_fragment
  {
    VALUE_NODE($$) = ast_node_create(&node_type_fragments);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  cmd_fragments cmd_fragment
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($2));
    
    ORIGINAL_NODE_NODE_APPEND($1, $2);
  } |
  cmd_fragments cmd_keyword_as_string
  {
    ast_node* fragment = ast_node_create(&node_type_fragment);
    ast_node_set_string(fragment, bstrcpy(VALUE($2)));
    
    fragment->original = bstrcpy(VALUE($2));
    
    ast_node_append_child(VALUE_NODE($1), fragment);
    
    ORIGINAL_NODE_APPEND($1, $2);
  };
  
cmd_keyword_as_string:
  KEYWORD_IF       { $$ = $1; } |
  KEYWORD_ELSE     { $$ = $1; } |
  KEYWORD_WHILE    { $$ = $1; } |
  KEYWORD_FOR      { $$ = $1; } |
  KEYWORD_FOREACH  { $$ = $1; } |
  KEYWORD_DO       { $$ = $1; } |
  KEYWORD_BREAK    { $$ = $1; } |
  KEYWORD_CONTINUE { $$ = $1; } |
  KEYWORD_AS       { $$ = $1; } ;
  
cmd_fragment:
  cmd_fragment_or_variable
  {
    VALUE_NODE($$) = ast_node_create(&node_type_fragment);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  cmd_number
  {
    $$ = $1;
  } |
  SINGLE_QUOTED
  {
    VALUE_NODE($$) = ast_node_create(&node_type_single_quoted);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  DOUBLE_QUOTED
  {
    VALUE_NODE($$) = ast_node_create(&node_type_double_quoted);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  CMD_DOLLAR VARIABLE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($2)));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  CMD_DOLLAR CMD_NUMBER
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_number(VALUE_NODE($$), VALUE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  CMD_DOLLAR QUESTION_MARK
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($2)));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  CMD_BEGIN_COMMAND cmd_pipeline END_PAREN
  {
    ast_node* empty_options;
    empty_options = ast_node_create(&node_type_key_values);
  
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ast_node_set_string(VALUE_NODE($2), bstrcpy(&pipeline_type_expression));
    ast_node_append_child(VALUE_NODE($2), empty_options);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } |
  /* This rule changes to expression mode and back again */
  CMD_DOLLAR CMD_BEGIN_SQUARE expr_key_values EXPR_END_SQUARE_BEGIN_COMMAND cmd_pipeline END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($5));
    
    ast_node_set_string(VALUE_NODE($5), bstrcpy(&pipeline_type_expression));
    ast_node_append_child(VALUE_NODE($5), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
  } |
  /* This rule changes to expression mode and back again */
  CMD_BEGIN_PAREN expression END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } ;
  
cmd_number:
  CMD_NUMBER
  {
    VALUE_NODE($$) = ast_node_create(&node_type_number);
    ast_node_set_number(VALUE_NODE($$), VALUE($1));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } ;
  
cmd_fragment_or_variable:
  CMD_MAP { $$ = $1; } |
  CMD_FRAGMENT { $$ = $1; } |
  VARIABLE { $$ = $1; } ;
  
cmd_function_declaration:
  CMD_BEGIN_PAREN END_PAREN cmd_optional_whitespace CMD_MAP cmd_optional_whitespace BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_function);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($7));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
    ORIGINAL_NODE_NODE_APPEND($$, $7);
    ORIGINAL_NODE_APPEND($$, $8);
  };

  
/* ---------- Rules that appear only in expressions ------------------------ */

expr_fragments_or_function_declaration:
  expr_fragments
  {
    $$ = $1;
  } |
  expr_function_declaration
  {
    $$ = $1;
  } ;
  
expr_key_values:
  expr_key_name
  {
    ast_node* key_value;
    VALUE_NODE($$) = ast_node_create(&node_type_key_values);
    key_value = ast_node_create(&node_type_key_value);
    ast_node_set_string(key_value, bstrcpy(VALUE($1)));
    ast_node_append_child(VALUE_NODE($$), key_value);
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  expr_key_name EXPR_EQUALS expr_fragment
  {
    ast_node* key_value;
    VALUE_NODE($$) = ast_node_create(&node_type_key_values);
    key_value = ast_node_create(&node_type_key_value);
    ast_node_set_string(key_value, bstrcpy(VALUE($1)));
    ast_node_append_child(key_value, VALUE_NODE($3));
    ast_node_append_child(VALUE_NODE($$), key_value);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expr_key_values EXPR_COMMA expr_key_name EXPR_EQUALS expr_fragment
  {
    ast_node* key_value;
    key_value = ast_node_create(&node_type_key_value);
    ast_node_set_string(key_value, bstrcpy(VALUE($3)));
    ast_node_append_child(key_value, VALUE_NODE($5));
    ast_node_append_child(VALUE_NODE($1), key_value);
    
    ORIGINAL_NODE_APPEND($1, $2);
    ORIGINAL_NODE_APPEND($1, $3);
    ORIGINAL_NODE_APPEND($1, $4);
    ORIGINAL_NODE_NODE_APPEND($1, $5);
  } |
  expr_key_values EXPR_COMMA expr_key_name
  {
    ast_node* key_value;
    key_value = ast_node_create(&node_type_key_value);
    ast_node_set_string(key_value, bstrcpy(VALUE($3)));
    ast_node_append_child(VALUE_NODE($1), key_value);
    
    ORIGINAL_NODE_APPEND($1, $2);
    ORIGINAL_NODE_APPEND($1, $3);
  } ;
  
expr_key_name:
  expr_fragment_or_variable { $$ = $1; } |
  QUESTION_MARK { $$ = $1; } ;
  
expr_fragment_or_variable:
  EXPR_FRAGMENT { $$ = $1; } |
  VARIABLE { $$ = $1; } ;
  
expr_function_declaration:
  EXPR_BEGIN_PAREN END_PAREN EXPR_MAP BEGIN_BRACE cmd_statements END_BRACE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_function);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($5));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
  };
  
expression:
  expr_fragment
  {
    VALUE_NODE($$) = VALUE_NODE($1);
  } |
  expression EXPR_ACCESS expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_access);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expression EXPR_BEGIN_PAREN END_PAREN
  {
    ast_node* arguments = ast_node_create(&node_type_arguments);
    
    VALUE_NODE($$) = ast_node_create(&node_type_invocation);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), arguments);
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } |
  expression EXPR_BEGIN_PAREN expr_comma_arguments END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_invocation);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
  } |
  expression EXPR_DIVIDE expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_divide);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expression EXPR_MULTIPLY expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_multiply);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expression EXPR_ADD expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_add);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expression EXPR_MINUS expression
  {
    VALUE_NODE($$) = ast_node_create(&node_type_minus);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expr_function_declaration
  {
    $$ = $1;
  } ;

expr_fragments:
  expr_fragment
  {
    VALUE_NODE($$) = ast_node_create(&node_type_fragments);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  expr_fragments expr_fragment
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($2));
    
    ORIGINAL_NODE_NODE_APPEND($1, $2);
  } |
  expr_fragments expr_keyword_as_string
  {
    ast_node* fragment = ast_node_create(&node_type_fragment);
    ast_node_set_string(fragment, bstrcpy(VALUE($2)));
    
    fragment->original = bstrcpy(VALUE($2));
    
    ast_node_append_child(VALUE_NODE($1), fragment);
    
    ORIGINAL_NODE_APPEND($1, $2);
  };
  
expr_keyword_as_string:
  KEYWORD_IF       { $$ = $1; } |
  KEYWORD_ELSE     { $$ = $1; } |
  KEYWORD_WHILE    { $$ = $1; } |
  KEYWORD_FOR      { $$ = $1; } |
  KEYWORD_FOREACH  { $$ = $1; } |
  KEYWORD_DO       { $$ = $1; } |
  KEYWORD_BREAK    { $$ = $1; } |
  KEYWORD_CONTINUE { $$ = $1; } |
  KEYWORD_AS       { $$ = $1; } ;
  
expr_fragment:
  expr_fragment_or_variable
  {
    VALUE_NODE($$) = ast_node_create(&node_type_fragment);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  expr_number
  {
    $$ = $1;
  } |
  PHP
  {
    VALUE_NODE($$) = ast_node_create(&node_type_php);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  SINGLE_QUOTED
  {
    VALUE_NODE($$) = ast_node_create(&node_type_single_quoted);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  DOUBLE_QUOTED
  {
    VALUE_NODE($$) = ast_node_create(&node_type_double_quoted);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($1)));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } |
  EXPR_DOLLAR VARIABLE
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($2)));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  EXPR_DOLLAR EXPR_NUMBER
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_number(VALUE_NODE($$), VALUE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  EXPR_DOLLAR QUESTION_MARK
  {
    VALUE_NODE($$) = ast_node_create(&node_type_variable);
    ast_node_set_string(VALUE_NODE($$), bstrcpy(VALUE($2)));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  EXPR_BEGIN_COMMAND cmd_pipeline END_PAREN
  {
    ast_node* empty_options;
    empty_options = ast_node_create(&node_type_key_values);
  
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ast_node_set_string(VALUE_NODE($2), bstrcpy(&pipeline_type_expression));
    ast_node_append_child(VALUE_NODE($2), empty_options);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } |
  EXPR_DOLLAR EXPR_BEGIN_SQUARE expr_key_values EXPR_END_SQUARE_BEGIN_COMMAND cmd_pipeline END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($5));
    
    ast_node_set_string(VALUE_NODE($5), bstrcpy(&pipeline_type_expression));
    ast_node_append_child(VALUE_NODE($5), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
    ORIGINAL_NODE_NODE_APPEND($$, $5);
    ORIGINAL_NODE_APPEND($$, $6);
  } |
  EXPR_BEGIN_PAREN expression END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_expression);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } |
  expr_array_decl
  {
    $$ = $1;
  } ;
  
expr_array_decl:
  EXPR_BEGIN_ARRAY END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_decl);
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
  } |
  EXPR_BEGIN_ARRAY expr_array_def END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_decl);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
  } |
  EXPR_BEGIN_ARRAY expr_array_def EXPR_COMMA END_PAREN
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_decl);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($2));
    
    ORIGINAL_NODE_APPEND($$, $1);
    ORIGINAL_NODE_NODE_APPEND($$, $2);
    ORIGINAL_NODE_APPEND($$, $3);
    ORIGINAL_NODE_APPEND($$, $4);
  } ;
  
expr_array_def:
  expr_array_element
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_def);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  expr_array_def EXPR_COMMA expr_array_element
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($1, $2);
    ORIGINAL_NODE_NODE_APPEND($1, $3);
  } ;
  
expr_array_element:
  expr_fragment EXPR_MAP expr_fragment
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_element);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($3));

    ORIGINAL_NODE_NODE_APPEND($$, $1);
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } |
  expr_fragment
  {
    VALUE_NODE($$) = ast_node_create(&node_type_array_element);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));

    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } ;
  
expr_number:
  EXPR_NUMBER
  {
    VALUE_NODE($$) = ast_node_create(&node_type_number);
    ast_node_set_number(VALUE_NODE($$), VALUE($1));
    
    ORIGINAL_NODE_APPEND($$, $1);
  } ;

expr_comma_arguments:
  expr_fragments
  {
    VALUE_NODE($$) = ast_node_create(&node_type_arguments);
    ast_node_append_child(VALUE_NODE($$), VALUE_NODE($1));
    
    ORIGINAL_NODE_NODE_APPEND($$, $1);
  } |
  expr_comma_arguments EXPR_COMMA expr_fragments
  {
    ast_node_append_child(VALUE_NODE($1), VALUE_NODE($3));
    
    ORIGINAL_NODE_APPEND($$, $2);
    ORIGINAL_NODE_NODE_APPEND($$, $3);
  } ;

%%

const char* lookup_name(int token) {
  return yytname[YYTRANSLATE(token)];
}
  