#ifndef LANAB_CONTEXT
#define LANAB_CONTEXT

#include <iostream>
using namespace std;

class LanAB_Context
{
public:
	void* scanner;
	int result;
	int a;
	int b;
	istream* is;
	int esc_depth;

public:
	LanAB_Context(istream* is = &cin)
	{
		init_scanner();
		this->is = is;
		a = 1;
		b = 1;
	}

	virtual ~LanAB_Context()
	{
		destroy_scanner();
	}

// Defined in LanAB.l
protected:
	void init_scanner();	
	void destroy_scanner();
};

int LanAB_parse(LanAB_Context*);

#endif // LANAB_CONTEXT
