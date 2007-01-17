// header file for compact_echo.cpp

#include <phc/Tree_transform.h>
class Compact_echo:public Tree_transform {
private:
	void push_back_echo(AST_actual_parameter_list* pl,AST_statement_list* out);
	void breakup_string_cats(AST_actual_parameter* ap,AST_actual_parameter_list* pl);
public:
	AST_statement_list* pre_statement_list(AST_statement_list* in);
};
