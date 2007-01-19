#include <iostream>
#include "LanAB_Context.h"

using namespace std;

int main()
{
	LanAB_Context context;

	if(!LanAB_parse(&context))
	{
		cout << context.result << endl;
	}
}
