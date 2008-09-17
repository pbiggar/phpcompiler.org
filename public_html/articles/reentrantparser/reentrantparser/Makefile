demo: main.o lex.LanAB_.o LanAB.tab.o lex.LanCD_.o LanCD.tab.o
	g++ -g -o demo main.o lex.LanAB_.o LanAB.tab.o lex.LanCD_.o LanCD.tab.o -lfl

main.o: main.cpp
	g++ -g -c -o main.o main.cpp

lex.LanAB_.o: lex.LanAB_.c LanAB.tab.h
	g++ -g -c -o lex.LanAB_.o lex.LanAB_.c

lex.LanAB_.c: LanAB.l
	flex LanAB.l

LanAB.tab.o: LanAB.tab.c
	g++ -g -c -o LanAB.tab.o LanAB.tab.c

LanAB.tab.c LanAB.tab.h: LanAB.y
	bison LanAB.y

lex.LanCD_.o: lex.LanCD_.c LanCD.tab.h
	g++ -g -c -o lex.LanCD_.o lex.LanCD_.c

lex.LanCD_.c: LanCD.l
	flex LanCD.l

LanCD.tab.o: LanCD.tab.c
	g++ -g -c -o LanCD.tab.o LanCD.tab.c

LanCD.tab.c LanCD.tab.h: LanCD.y
	bison LanCD.y

.PHONY: clean
clean:
	rm -f demo *.o
	rm -f LanAB.tab.c LanAB.tab.h
	rm -f lex.LanAB_.c lex.LanAB_.o
	rm -f LanCD.tab.c LanCD.tab.h
	rm -f lex.LanCD_.c lex.LanCD_.o
