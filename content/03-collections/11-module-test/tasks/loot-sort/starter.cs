using System;
using System.Collections.Generic;

string[] drops = { "монета", "кость", "зелье", "меч", "кость", "монета" };
string junk = "кость";
List<string> bag = new List<string>();

// 1. Переложи в bag всё, кроме junk
// 2. Если есть зелье — выпей его
// 3. Выведи рюкзак с номерами и количество
