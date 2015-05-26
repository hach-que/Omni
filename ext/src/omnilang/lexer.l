%{

#include <bstring.h>
#include "parser.h"

#ifdef YYDEBUG
#undef YYDEBUG
#endif
#define YYDEBUG 1

#define OD(s) { fprintf(stderr, s "\n"); }
#define OCOPY(s) { yylval.s.original = bfromcstr(yytext); }
#define OCAT(s) { bcatcstr(yylval.s.original, yytext); }

void reset_to_initial();
void lex_push(int state);
void lex_pop();
int lex_pop_potential(int state);

extern int can_perform_colon_jump;
extern int expects_context_sensitive_cmd_map;
const_bstring get_current_state_name();

#define CANCEL_JUMP() { can_perform_colon_jump = 0; }
#define C_OCOPY(s) { CANCEL_JUMP(); OCOPY(s); }
#define C_OCAT(s) { CANCEL_JUMP(); OCAT(s); }

%}

%option yylineno
%option case-insensitive

%x IN_SINGLE_STRING
%x IN_DOUBLE_STRING
%x IN_PHP
%x IN_EXPR
%x IN_EXPR_COLON
%x IN_CMD

%%

    /* Comments */

<IN_EXPR,IN_CMD>\#[^\n]*\n                              /* comment */;

    /* Both expression and command modes */

<IN_EXPR,IN_CMD>\'                                      { C_OCOPY(string); yylval.string.value = bfromcstr(""); lex_push(IN_SINGLE_STRING); }
<IN_EXPR,IN_CMD>\"                                      { C_OCOPY(string); yylval.string.value = bfromcstr(""); lex_push(IN_DOUBLE_STRING); }
<IN_SINGLE_STRING>[^'\r\n]*                             { C_OCAT(string); bcatcstr(yylval.string.value, yytext); }
<IN_SINGLE_STRING>\'                                    { C_OCAT(string); lex_pop(); return SINGLE_QUOTED; }
<IN_SINGLE_STRING><<EOF>>                               { return UNTERMINATED_LEXING_BLOCK; }
<IN_DOUBLE_STRING>[^"\r\n]*                             { C_OCAT(string); bcatcstr(yylval.string.value, yytext); }
<IN_DOUBLE_STRING>\"                                    { C_OCAT(string); lex_pop(); return DOUBLE_QUOTED; }
<IN_DOUBLE_STRING><<EOF>>                               { return UNTERMINATED_LEXING_BLOCK; }

<IN_EXPR,IN_CMD>if                                      { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_IF; }
<IN_EXPR,IN_CMD>else                                    { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_ELSE; }
<IN_EXPR,IN_CMD>while                                   { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_WHILE; }
<IN_EXPR,IN_CMD>for                                     { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_FOR; }
<IN_EXPR,IN_CMD>foreach                                 { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_FOREACH; }
<IN_EXPR,IN_CMD>do                                      { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_DO; }
<IN_EXPR,IN_CMD>break                                   { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_BREAK; }
<IN_EXPR,IN_CMD>continue                                { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_CONTINUE; }
<IN_EXPR,IN_CMD>as                                      { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_AS; }
<IN_EXPR,IN_CMD>return                                  { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return KEYWORD_RETURN; }

    /* Placed here for priority */
<IN_EXPR>[0-9]+                                         { C_OCOPY(number); yylval.number.value = strtoul(yytext, NULL, 10); return EXPR_NUMBER; }
<IN_CMD>[0-9]+                                          { C_OCOPY(number); yylval.number.value = strtoul(yytext, NULL, 10); return CMD_NUMBER; }
<IN_EXPR>\]\(                                           { C_OCOPY(token); lex_pop(); lex_push(IN_CMD); return EXPR_END_SQUARE_BEGIN_COMMAND; }

<IN_EXPR,IN_CMD>[a-zA-Z0-9_][a-zA-Z_0-9]*               { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return VARIABLE; }

<IN_EXPR,IN_CMD>\)                                      { C_OCOPY(token); lex_pop(); return END_PAREN; }
<IN_EXPR,IN_CMD>\]                                      { C_OCOPY(token); lex_pop(); return END_SQUARE; }
    
<IN_EXPR,IN_CMD>\?                                      { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return QUESTION_MARK; }

    /* Command only */

<IN_CMD>\=\>                                            { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return CMD_MAP; }
<IN_CMD>[a-zA-Z\/_0-9\.\-\~\^\=\*\+\?\%\@\<\>]+         { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return CMD_FRAGMENT; }
<IN_CMD>\\\                                             { C_OCOPY(string); yylval.string.value = bfromcstr(" "); return CMD_FRAGMENT; }
<IN_CMD>\\                                              { C_OCOPY(string); yylval.string.value = bfromcstr("\\"); return CMD_FRAGMENT; }

<IN_CMD>\$\(                                            { C_OCOPY(token); lex_push(IN_CMD); return CMD_BEGIN_COMMAND; }
<IN_CMD>\(                                              { C_OCOPY(token); lex_push(IN_EXPR); return CMD_BEGIN_PAREN; }
<IN_CMD>\[                                              { C_OCOPY(token); lex_push(IN_EXPR); return CMD_BEGIN_SQUARE; }
<IN_CMD>\$                                              { C_OCOPY(token); return CMD_DOLLAR; }

<IN_CMD>[ \t]*&&[ \t]*                                  { C_OCOPY(token); return CMD_DOUBLE_AMPERSAND; }
<IN_CMD>[ \t]*\|\|[ \t]*                                { C_OCOPY(token); return CMD_DOUBLE_PIPE; }
<IN_CMD>[ \t]*&[ \t]*                                   { C_OCOPY(token); return CMD_AMPERSAND; }
<IN_CMD>[ \t]*\|[ \t]*                                  { C_OCOPY(token); return CMD_PIPE; }
<IN_CMD>[ \t]*\<[ \t]*                                  { C_OCOPY(token); return CMD_REDIRECT_IN; }
<IN_CMD>[ \t]*\>[ \t]*                                  { C_OCOPY(token); return CMD_REDIRECT_OUT; }
<IN_CMD>[ \t]+:[ \t]*                                   { if (can_perform_colon_jump) { OCOPY(token); lex_push(IN_EXPR_COLON); return CMD_COLON; } else { REJECT; } }
<IN_CMD>[ \t]*:[ \t]+                                   { if (can_perform_colon_jump) { OCOPY(token); lex_push(IN_EXPR_COLON); return CMD_COLON; } else { REJECT; } }
<IN_CMD>:                                               { if (can_perform_colon_jump) { OCOPY(token); lex_push(IN_EXPR_COLON); return CMD_COLON; } else { C_OCOPY(string); yylval.string.value = bfromcstr(":"); return CMD_FRAGMENT; } }

<IN_CMD>[ \t]*\;[ \t]*                                  { C_OCOPY(token); return CMD_SEMICOLON; }

<IN_CMD>[ \t]+                                          { C_OCOPY(token); return CMD_WHITESPACE; }
<IN_CMD>\\[ \t]*(\r\n|\r|\n)[ \t]*                      { C_OCOPY(token); return CMD_WHITESPACE; }
<IN_CMD>[ \t]*(\r\n|\r|\n)[ \t]*                        { C_OCOPY(token); can_perform_colon_jump = 1; return CMD_TERMINATING_NEWLINE; }

<IN_CMD>[ \t]*\{[ \t]*                                  { C_OCOPY(token); lex_push(IN_CMD); return BEGIN_BRACE; }
<IN_CMD>[ \t]*\}[ \t]*                                  { C_OCOPY(token); lex_pop(); return END_BRACE; }

    /* Expression only */

<IN_EXPR>\<\?php                                        { C_OCOPY(string); lex_push(IN_PHP); yylval.string.value = bfromcstr(yytext); }
<IN_PHP>\?\>                                            { C_OCAT(string); lex_pop(); bcatcstr(yylval.string.value, yytext); return PHP; }
<IN_PHP>.|\r|\n                                         { C_OCAT(string); bcatcstr(yylval.string.value, yytext); }
<IN_PHP><<EOF>>                                         { return UNTERMINATED_LEXING_BLOCK; }

<IN_EXPR>[a-zA-Z_\.\~]                                  { C_OCOPY(string); yylval.string.value = bfromcstr(yytext); return EXPR_FRAGMENT; }
<IN_EXPR>[a-zA-Z\/_\.\-\~\^\*\+\%][a-zA-Z\/_0-9\.\-\~\^\:\=\*\+\%]*[a-zA-Z\/_0-9\.\~\^\:\=\*\+\%]  { OCOPY(string); yylval.string.value = bfromcstr(yytext); return EXPR_FRAGMENT; }
<IN_EXPR>[a-zA-Z\/_\.\-\~\^\=\*\+\%][a-zA-Z\/_0-9\.\-\~\^\:\=\*\+\%]*[a-zA-Z\/_0-9\.\~\^\:\*\+\%]  { OCOPY(string); yylval.string.value = bfromcstr(yytext); return EXPR_FRAGMENT; }
<IN_EXPR>[a-zA-Z\/_\.\-\~\^\=\*\+\%][a-zA-Z\/_0-9\.\-\~\^\:\=\*\+\%]+[a-zA-Z\/_0-9\.\~\^\:\=\*\+\%]  { OCOPY(string); yylval.string.value = bfromcstr(yytext); return EXPR_FRAGMENT; }

<IN_EXPR>\$\(                                           { C_OCOPY(token); lex_push(IN_CMD); return EXPR_BEGIN_COMMAND; }
<IN_EXPR>\$                                             { C_OCOPY(token); return EXPR_DOLLAR; }

<IN_EXPR>\@\(                                           { C_OCOPY(token); lex_push(IN_EXPR); return EXPR_BEGIN_ARRAY; }
<IN_EXPR>\-\>                                           { C_OCOPY(token); return EXPR_ACCESS; }
<IN_EXPR>\=\>                                           { C_OCOPY(token); return EXPR_MAP; }
<IN_EXPR>\(                                             { C_OCOPY(token); lex_push(IN_EXPR); return EXPR_BEGIN_PAREN; }
<IN_EXPR>\[                                             { C_OCOPY(token); lex_push(IN_EXPR); return EXPR_BEGIN_SQUARE; }

<IN_EXPR>\{                                             { C_OCOPY(token); lex_push(IN_CMD); return BEGIN_BRACE; }
<IN_EXPR>\}                                             { C_OCOPY(token); lex_pop(); return END_BRACE; }

<IN_EXPR>&                                              { C_OCOPY(token); return EXPR_AMPERSAND; }
<IN_EXPR>\|                                             { C_OCOPY(token); return EXPR_PIPE; }
<IN_EXPR>\,                                             { C_OCOPY(token); return EXPR_COMMA; }
<IN_EXPR>\;                                             { C_OCOPY(token); return EXPR_SEMICOLON; }
<IN_EXPR>:                                              { C_OCOPY(token); return EXPR_COLON; }

<IN_EXPR>\+                                             { C_OCOPY(token); return EXPR_ADD; }
<IN_EXPR>\-                                             { C_OCOPY(token); return EXPR_MINUS; }
<IN_EXPR>\*                                             { C_OCOPY(token); return EXPR_MULTIPLY; }
<IN_EXPR>\/                                             { C_OCOPY(token); return EXPR_DIVIDE; }

<IN_EXPR>\=\=                                           { C_OCOPY(token); return EXPR_EQUALS_COMPARE; }
<IN_EXPR>\!\=                                           { C_OCOPY(token); return EXPR_NOT_EQUALS; }
<IN_EXPR>\<\=                                           { C_OCOPY(token); return EXPR_LESS_THAN_EQUALS; }
<IN_EXPR>\<                                             { C_OCOPY(token); return EXPR_LESS_THAN; }
<IN_EXPR>\>\=                                           { C_OCOPY(token); return EXPR_GREATER_THAN_EQUALS; }
<IN_EXPR>\>                                             { C_OCOPY(token); return EXPR_GREATER_THAN; }

<IN_EXPR>\!                                             { C_OCOPY(token); return EXPR_NOT; }
<IN_EXPR>\=                                             { C_OCOPY(token); return EXPR_EQUALS_ASSIGN; }

<IN_EXPR>[ \t]+                                         /* ignore whitespace in expressions */
<IN_EXPR>\\[ \t]*(\r\n|\r|\n)[ \t]*                     /* ignore continued newlines in expressions */
<IN_EXPR>[ \t]*(\r\n|\r|\n)[ \t]*                       { 
    if (lex_pop_potential(IN_EXPR_COLON) == 1) { 
      C_OCOPY(token); 
      can_perform_colon_jump = 1; 
      return EXPR_TERMINATING_NEWLINE; 
    } else {
      /* ignore this terminating newline because it's not terminating in this context */
    } 
  }

    /* Default rule for unknown characters */

<*>.|\r|\n                                              {
    /*printf("unable to handle text %s in lexer when in state %s!\n", yytext, get_current_state_name()->data);*/
    return ERROR;
  }

%%

#include <simclist.h>

static list_t* state_stack = NULL;
int can_perform_colon_jump = 0;
int expects_context_sensitive_cmd_map = 0;
struct tagbstring state_initial = bsStatic("INITIAL");
struct tagbstring state_in_single_string = bsStatic("IN_SINGLE_STRING");
struct tagbstring state_in_double_string = bsStatic("IN_DOUBLE_STRING");
struct tagbstring state_in_php = bsStatic("IN_PHP");
struct tagbstring state_in_expr = bsStatic("IN_EXPR");
struct tagbstring state_in_expr_colon = bsStatic("IN_EXPR_COLON");
struct tagbstring state_in_cmd = bsStatic("IN_CMD");
struct tagbstring state_unknown = bsStatic("<unknown>");

void reset_to_initial() {
  int* value;
  
  BEGIN(IN_CMD);
  
  if (state_stack == NULL) {
    state_stack = malloc(sizeof(list_t));
    list_init(state_stack);
  }
  
  can_perform_colon_jump = 1;
  expects_context_sensitive_cmd_map = 0;
  
  value = malloc(sizeof(int));
  *value = IN_CMD;
  list_append(state_stack, value);
}

void lex_push(int state) {
  int* value;
  int state_real;
  value = malloc(sizeof(int));
  *value = state;
  
  list_append(state_stack, value);
  if (state == IN_EXPR_COLON) {
    state_real = IN_EXPR;
  } else {
    state_real = state;
  }
  
  BEGIN(state_real);
}

void lex_pop() {
  int size, state, state_real;
  size = list_size(state_stack);
  
  if (size == 1) {
    // TODO raise error
    return;
  }
  
  list_delete_at(state_stack, size - 1);
  
  state = *((int*)list_get_at(state_stack, size - 2));
  if (state == IN_EXPR_COLON) {
    state_real = IN_EXPR;
  } else {
    state_real = state;
  }
  
  BEGIN(state_real);
}

int lex_pop_potential(int tgt_state) {
  int size, state, state_real;
  int* current;
  size = list_size(state_stack);
  
  if (size == 1) {
    // TODO raise error
    return 0;
  }
  
  current = list_get_at(state_stack, size - 1);
  if (*current == tgt_state) {
    list_delete_at(state_stack, size - 1);
    state = *((int*)list_get_at(state_stack, size - 2));
    if (state == IN_EXPR_COLON) {
      state_real = IN_EXPR;
    } else {
      state_real = state;
    }
    
    BEGIN(state_real);
    return 1;
  } else {
    return 0;
  }
}

const_bstring get_current_state_name() {
  switch (YYSTATE) {
    case INITIAL:
      return &state_initial;
    case IN_SINGLE_STRING:
      return &state_in_single_string;
    case IN_DOUBLE_STRING:
      return &state_in_double_string;
    case IN_PHP:
      return &state_in_php;
    case IN_EXPR:
      return &state_in_expr;
    case IN_EXPR_COLON:
      return &state_in_expr_colon;
    case IN_CMD:
      return &state_in_cmd;
    default:
      return &state_unknown;
  }
}