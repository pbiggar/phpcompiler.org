%pure-parser
%name-prefix="LanCD_"
%locations
%defines
%error-verbose
%parse-param { LanCD_Context* context }
%lex-param { void* scanner  }

%union
{
	int integer;
	char* cptr;
}

%token <integer> C
%token <integer> D
%token <cptr> ESCAPE 
%token ERR

%type <integer> lancd

%{
	#include <iostream>
	#include <sstream>
	#include "LanAB_Context.h"
	#include "LanCD_Context.h"
	
	using namespace std;
	
	int LanCD_lex(YYSTYPE* lvalp, YYLTYPE* llocp, void* scanner);		

	void LanCD_error(YYLTYPE* locp, LanCD_Context* context, const char* err)
	{
		cout << locp->first_line << ":" << err << endl;
	}

	#define scanner context->scanner
%}

%%

start:
	  lancd
	  	{ context->result = $1; }
	;

lancd:
	  C lancd
	  	{ $$ = $1 + $2; }
	| D lancd
		{ $$ = $1 + $2; }
	| ESCAPE lancd 
		{
			{
				istringstream* is = new istringstream($1);
				LanAB_Context context(is);
				LanAB_parse(&context);
				$$ = context.result + $2; 
			}
		}
	| /* empty */
		{ $$ = 0; }
	;
