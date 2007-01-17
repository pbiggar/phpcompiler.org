%option reentrant
%option prefix="LanAB_"
%option bison-bridge
%option bison-locations
%option noyywrap
%option yylineno

%{
	#include "LanAB_Context.h"
	#include "LanAB.tab.h"

	#define YY_EXTRA_TYPE LanAB_Context*
	#define YY_USER_ACTION yylloc->first_line = yylineno;

	#define YY_INPUT(buf,result,max_size) \
	{ \
		char c; \
		(*yyextra->is) >> c; \
		if(yyextra->is->eof()) \
			result = YY_NULL; \
		else { \
			buf[0] = c; \
			result = 1; \
		} \
	}
%}

%x ESC

%%

"a"			yylval->integer = yyextra->a++; return A; 
"b"			yylval->integer = yyextra->b++; return B;
"["			yyextra->esc_depth = 1; BEGIN(ESC);
.			return ERR;
\n			/* ignore */

<ESC>"]"	%{
				yyextra->esc_depth--;
				if(yyextra->esc_depth == 0)
				{
					yylval->cptr = strndup(yytext, yyleng-1); 
					BEGIN(INITIAL); 
					return ESCAPE;
				}
				else
				{
					yymore();
				}
			%}
<ESC>"["	yymore(); yyextra->esc_depth++;;
<ESC>.		yymore();

%%

void LanAB_Context::init_scanner()
{
	yylex_init(&scanner);
	yyset_extra(this, scanner);
}

void LanAB_Context::destroy_scanner()
{
	yylex_destroy(scanner);
}
