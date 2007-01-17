%pure-parser
%name-prefix="LanAB_"
%locations
%defines
%error-verbose
%parse-param { LanAB_Context* context }
%lex-param { void* scanner  }

%union
{
	int integer;
	char* cptr;
}

%token <integer> A
%token <integer> B
%token <cptr> ESCAPE 
%token ERR

%type <integer> lanab

%{
	#include <iostream>
	#include <sstream>
	#include "LanAB_Context.h"
	#include "LanCD_Context.h"

	using namespace std;

	int LanAB_lex(YYSTYPE* lvalp, YYLTYPE* llocp, void* scanner);		

	void LanAB_error(YYLTYPE* locp, LanAB_Context* context, const char* err)
	{
		cout << locp->first_line << ":" << err << endl;
	}

	#define scanner context->scanner
%}

%%

start:
	  lanab
	  	{ context->result = $1; }
	;

lanab:
	  A lanab
	  	{ $$ = $1 + $2; }
	| B lanab
		{ $$ = $1 + $2; }
	| ESCAPE lanab
		{
			{
				istringstream* is = new istringstream($1);
				LanCD_Context context(is);
				LanCD_parse(&context);
				$$ = context.result + $2;
			}
		}
	| /* empty */
		{ $$ = 0; }
	;
