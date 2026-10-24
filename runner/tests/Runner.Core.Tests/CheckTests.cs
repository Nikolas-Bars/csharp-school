using CSharpSchool.Runner.Protocol;

namespace CSharpSchool.Runner.Tests;

public sealed class CheckTests
{
    private static readonly RunnerService Service = new();

    private static TestResponse Test(string code, params Check[] checks) => Service.Test(new TestRequest(code, checks));

    [Fact]
    public void OutputMatchesIgnoringLineEndingsAndTrailingSpaces()
    {
        var response = Test("using System;\nConsole.Write(\"HP: 100  \\r\\nУрон: 5\\n\\n\");", new OutputCheck("HP: 100\nУрон: 5"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void WrongOutputShowsWhatWasPrinted()
    {
        var result = Assert.Single(Test("System.Console.Write(\"HP: 99\");", new OutputCheck("HP: 100")).Results);

        Assert.False(result.Passed);
        Assert.Equal(("HP: 100", "HP: 99"), (result.Expected, result.Actual));
    }

    [Fact]
    public void OutputContainsAndEachCheckHasItsOwnInput()
    {
        const string code = "using System;\nint hp = int.Parse(Console.ReadLine());\nConsole.WriteLine(hp > 0 ? \"Жив\" : \"Побеждён\");";

        var response = Test(
            code,
            new OutputCheck("Жив", Stdin: "10", Match: OutputMatch.Contains),
            new OutputCheck("Побеждён", Stdin: "0"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void CrashFailsOutputCheck()
    {
        var result = Assert.Single(Test("int zero = 0;\nSystem.Console.Write(1 / zero);", new OutputCheck("0")).Results);

        Assert.False(result.Passed);
        Assert.Equal("DivideByZeroException", result.Error?.Type);
    }

    [Fact]
    public void VariableNameIsCaseSensitiveAndTypeIsInferred()
    {
        var response = Test(
            "var hp = 100;\nstring HeroName = \"Аня\";",
            new VariableCheck("hp", "int"),
            new VariableCheck("HP"),
            new VariableCheck("HeroName", "int"));

        Assert.Equal([true, false, false], response.Results.Select(result => result.Passed));
        Assert.Equal("string", response.Results[2].Actual);
    }

    [Fact]
    public void ForeachVariableAndGenericTypesCount()
    {
        var response = Test(
            "using System.Collections.Generic;\nvar items = new List<string> { \"меч\" };\nforeach (var item in items) { }",
            new VariableCheck("items", "List<string>"),
            new VariableCheck("item", "string"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void ConstructsAreCounted()
    {
        var response = Test(
            "for (int i = 0; i < 3; i++) { if (i == 1) { } else { } }",
            new ConstructCheck(Construct.For),
            new ConstructCheck(Construct.If, Min: 2),
            new ConstructCheck(Construct.Else));

        Assert.Equal([true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal(("2", "1"), (response.Results[1].Expected, response.Results[1].Actual));
    }

    [Fact]
    public void ConstructorsAndPropertiesAreCounted()
    {
        const string code = """
            Hero hero = new Hero(5);
            System.Console.WriteLine(hero.Hp);

            class Hero
            {
                private int armor;

                public Hero(int hp) { Hp = hp; }

                public Hero() : this(100) { }

                public int Hp { get; private set; }

                public int Armor => armor;

                public int Heal(int value) { return Hp + value; }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.Constructor, Min: 2),
            new ConstructCheck(Construct.Property, Min: 2),
            new ConstructCheck(Construct.Constructor, Min: 3));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
        Assert.Equal("2", response.Results[2].Actual);
    }

    [Fact]
    public void PrivateSetAndThisAreCounted()
    {
        const string code = """
            Hero hero = new Hero(5);
            System.Console.WriteLine(hero.Hp + hero.Mana);

            class Hero
            {
                private int hp;

                public Hero(int hp) { this.hp = hp; Mana = this.hp; }

                public int Hp { get { return hp; } private set { hp = value; } }

                public int Mana { get; set; }

                public int Armor { get; private set; }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.PrivateSet, Min: 2),
            new ConstructCheck(Construct.This, Min: 2),
            new ConstructCheck(Construct.This, Min: 3));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
        Assert.Equal("2", response.Results[2].Actual);
    }

    [Fact]
    public void PublicSetIsNotPrivateSet()
    {
        var response = Test(
            "System.Console.WriteLine(new Hero().Hp);\nclass Hero { public int Hp { get; set; } }",
            new ConstructCheck(Construct.PrivateSet),
            new ConstructCheck(Construct.This));

        Assert.Equal([false, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void EnumAndInheritanceConstructsAreCounted()
    {
        const string code = """
            using System;
            using System.Collections.Generic;

            List<Enemy> enemies = new List<Enemy> { new Orc("Грох"), new Goblin("Скрип") };
            foreach (Enemy enemy in enemies)
            {
                enemy.Attack();
            }

            enum State { Idle, Walk, Attack }

            interface ITarget { }

            class Enemy : ITarget
            {
                protected string name;
                protected int hp;
                public State state;

                public Enemy(string name, int hp) { this.name = name; this.hp = hp; }

                public virtual void Attack() { Console.WriteLine(name); }
            }

            class Orc : Enemy
            {
                public Orc(string name) : base(name, 60) { }

                public override void Attack() { base.Attack(); }

                public override string ToString() { return name; }
            }

            class Goblin : Enemy
            {
                public Goblin(string name) : this(name, 30) { }

                protected Goblin(string name, int hp) : base(name, hp) { }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.Enum),
            new ConstructCheck(Construct.Inheritance, Min: 2),
            new ConstructCheck(Construct.Inheritance, Min: 3),
            new ConstructCheck(Construct.Virtual),
            new ConstructCheck(Construct.Override, Min: 2),
            new ConstructCheck(Construct.Protected, Min: 3),
            new ConstructCheck(Construct.BaseConstructor, Min: 2),
            new ConstructCheck(Construct.BaseConstructor, Min: 3));

        Assert.Equal([true, true, false, true, true, true, true, false], response.Results.Select(result => result.Passed));
        // Наследники — Orc и Goblin; Enemy : ITarget — только интерфейс, базового класса у него нет
        Assert.Equal("2", response.Results[2].Actual);
        // : this(…) — не вызов базового конструктора
        Assert.Equal("2", response.Results[7].Actual);
    }

    [Fact]
    public void ProtectedSetOfPropertyCounts()
    {
        const string code = """
            System.Console.WriteLine(new Enemy().Hp);

            class Enemy
            {
                public int Hp { get; protected set; }
            }
            """;

        var response = Test(code, new ConstructCheck(Construct.Protected), new ConstructCheck(Construct.Protected, Min: 2));

        Assert.Equal([true, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void ClassWithoutBaseHasNoInheritanceOrOverrides()
    {
        const string code = """
            System.Console.WriteLine(new Enemy().Name());

            class Enemy
            {
                private int hp;

                public string Name() { return "Гоблин" + hp; }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.Enum),
            new ConstructCheck(Construct.Inheritance),
            new ConstructCheck(Construct.Virtual),
            new ConstructCheck(Construct.Override),
            new ConstructCheck(Construct.Protected),
            new ConstructCheck(Construct.BaseConstructor));

        Assert.All(response.Results, result => Assert.Equal(("1", "0"), (result.Expected, result.Actual)));
    }

    [Fact]
    public void EnumValueDictionaryAndPairAreFoundByFullName()
    {
        const string code = """
            using System;
            using System.Collections.Generic;

            State state = State.Walk;
            Dictionary<string, int> items = new Dictionary<string, int>();
            items.Add("меч", 1);
            items["зелье"] = 2;
            if (items.TryGetValue("меч", out int swords) && items.ContainsKey("зелье"))
            {
                items.Remove("зелье");
            }
            foreach (KeyValuePair<string, int> pair in items)
            {
                Console.WriteLine($"{pair.Key}: {pair.Value + swords}");
            }
            switch (state)
            {
                case State.Attack:
                    Console.WriteLine("Атака");
                    break;
            }

            enum State { Idle, Walk, Attack }
            """;

        var response = Test(
            code,
            new VariableCheck("state", "State"),
            new VariableCheck("items", "Dictionary<string, int>"),
            new AccessCheck("State.Attack"),
            new AccessCheck("State.Walk"),
            new AccessCheck("State.Idle"),
            new AccessCheck("System.Collections.Generic.KeyValuePair.Key"),
            new CallCheck("System.Collections.Generic.Dictionary.Add"),
            new CallCheck("System.Collections.Generic.Dictionary.TryGetValue"),
            new CallCheck("System.Collections.Generic.Dictionary.ContainsKey"),
            new CallCheck("System.Collections.Generic.Dictionary.Remove"),
            new OutputCheck("меч: 2"));

        Assert.Equal([true, true, true, true, false, true, true, true, true, true, true], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void CallOfBaseMethodIsBaseClassMethod()
    {
        const string code = """
            using System;
            using System.Collections.Generic;

            List<Enemy> enemies = new List<Enemy> { new Orc() };
            foreach (Enemy enemy in enemies)
            {
                enemy.Attack();
            }

            class Enemy
            {
                public virtual void Attack() { Console.WriteLine("Удар"); }
            }

            class Orc : Enemy
            {
                public override void Attack()
                {
                    base.Attack();
                    Console.WriteLine("Рык");
                }
            }
            """;

        var response = Test(
            code,
            new CallCheck("Enemy.Attack", Caller: "Orc.Attack"),
            new CallCheck("Orc.Attack"),
            new OutputCheck("Удар\nРык"));

        // enemy.Attack() из List<Enemy> — вызов Enemy.Attack, хотя работает версия орка
        Assert.Equal([true, false, true], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void CallerCanBeConstructorOrProperty()
    {
        const string code = """
            Hero hero = new Hero();
            System.Console.WriteLine(hero.Hp);

            class Hero
            {
                public Hero() { Log(); }

                public int Hp { get { Count(); return 1; } }

                public int Mana => Count();

                private void Log() { }

                private int Count() { return 0; }
            }
            """;

        var response = Test(
            code,
            new CallCheck("Hero.Log", Caller: "Hero.Hero"),
            new CallCheck("Hero.Count", Caller: "Hero.Hp"),
            new CallCheck("Hero.Count", Caller: "Hero.Mana"),
            new CallCheck("Hero.Log", Caller: "Hero.Hp"));

        Assert.Equal([true, true, true, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void AccessCountsOnlyReadsOfFieldOrProperty()
    {
        const string code = """
            int hp = 50;
            Hero hero = new Hero { hp = hp };
            hero.hp = 10;
            hero.hp += 5;
            hero.hp++;
            hero.Mana = 3;
            System.Console.WriteLine(hp);
            System.Console.WriteLine(hero.Mana);

            class Hero
            {
                public int hp;

                public int Mana { get; set; }

                public int Total() { return this.hp + hp; }
            }
            """;

        var response = Test(
            code,
            new AccessCheck("Hero.hp"),
            new AccessCheck("Hero.hp", Min: 3),
            new AccessCheck("Hero.Mana"),
            new AccessCheck("Hero.Armor"));

        Assert.Equal([true, false, true, false], response.Results.Select(result => result.Passed));
        // Только this.hp и hp в Total: запись в hero.hp (=, +=, ++) и hp из основного кода не считаются
        Assert.Equal(("3", "2"), (response.Results[1].Expected, response.Results[1].Actual));
    }

    [Fact]
    public void AccessCatchesPrintingOriginalVariableInsteadOfField()
    {
        const string wrong = "int hp = 50;\nHero hero = new Hero();\nhero.hp = hp;\nSystem.Console.WriteLine(hp);\nclass Hero { public int hp; }";
        const string right = "int hp = 50;\nHero hero = new Hero();\nhero.hp = hp;\nSystem.Console.WriteLine(hero.hp);\nclass Hero { public int hp; }";

        Assert.False(Test(wrong, new AccessCheck("Hero.hp")).Passed);
        Assert.True(Test(right, new AccessCheck("Hero.hp")).Passed);
    }

    [Fact]
    public void FieldsAndMethodsAreNotConstructorsOrProperties()
    {
        var response = Test(
            "System.Console.WriteLine(new Hero().Hp);\nclass Hero { public int Hp = 1; public void Hero2() { } }",
            new ConstructCheck(Construct.Constructor),
            new ConstructCheck(Construct.Property));

        Assert.Equal([false, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void BreakCountsOnlyWhenItLeavesLoopAndContinueAlways()
    {
        const string code = """
            for (int i = 0; i < 5; i++)
            {
                switch (i)
                {
                    case 1:
                        continue;
                    default:
                        break;
                }

                while (true)
                {
                    break;
                }
            }
            """;

        var response = Test(
            code,
            new ConstructCheck(Construct.Break),
            new ConstructCheck(Construct.Break, Min: 2),
            new ConstructCheck(Construct.Continue));

        Assert.Equal([true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal("1", response.Results[1].Actual);
    }

    [Fact]
    public void NoBreakOrContinueInCode()
    {
        var response = Test(
            "switch (1) { case 1: break; }",
            new ConstructCheck(Construct.Break),
            new ConstructCheck(Construct.Continue));

        Assert.Equal([false, false], response.Results.Select(result => result.Passed));
        Assert.Equal("0", response.Results[0].Actual);
    }

    [Fact]
    public void CallFromCallerCountsOnlyInsideThatMethod()
    {
        const string code = """
            using System;
            Report(5);
            Console.WriteLine(GetStatus(1));

            void Report(int hp)
            {
                Func<string> later = () => GetStatus(hp);
                Console.WriteLine(later());
            }

            string GetStatus(int hp)
            {
                return hp > 0 ? "Жив" : "Побеждён";
            }
            """;

        var response = Test(
            code,
            new CallCheck("Program.GetStatus"),
            new CallCheck("Program.GetStatus", Caller: "Program.Report"),
            new CallCheck("Program.Report", Caller: "Program.GetStatus"),
            new CallCheck("System.Console.WriteLine", Caller: "Program.Report"));

        Assert.Equal([true, true, false, true], response.Results.Select(result => result.Passed));
        Assert.Equal(("2", "1"), (response.Results[0].Actual, response.Results[1].Actual));
    }

    [Fact]
    public void CallFromNestedMethodCountsForOuterCaller()
    {
        const string code = """
            Report();

            void Report()
            {
                void Inner()
                {
                    GetStatus();
                }

                Inner();
            }

            string GetStatus() => "Жив";
            """;

        var response = Test(
            code,
            new CallCheck("Program.GetStatus", Caller: "Program.Report"),
            new CallCheck("Program.GetStatus", Caller: "Program.Inner"));

        Assert.True(response.Passed);
    }

    [Fact]
    public void ExecutionEndIsReportedAfterEachRun()
    {
        var events = new List<string>();
        var response = Service.Test(
            new TestRequest("int height = 3;\nSystem.Console.Write(height);", [
                new OutputCheck("3"),
                new OutputCheck("5", Set: new Dictionary<string, System.Text.Json.JsonElement> { ["height"] = System.Text.Json.JsonSerializer.SerializeToElement(5) }),
                new ConstructCheck(Construct.For),
            ]),
            () => events.Add("start"),
            () => events.Add("end"));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
        Assert.Equal(["start", "end", "start", "end"], events);
    }

    [Fact]
    public void CallFromMainCodeDoesNotCountForCaller()
    {
        var response = Test(
            "int Twice(int a) => a * 2;\nint Show() => 1;\nSystem.Console.Write(Twice(Show()));",
            new CallCheck("Program.Twice", Caller: "Program.Show"));

        Assert.False(response.Passed);
    }

    [Fact]
    public void CallsAreFoundBySymbolNotByText()
    {
        var response = Test(
            "using System;\nConsole.WriteLine(Math.Max(1, 2));",
            new CallCheck("System.Console.WriteLine"),
            new CallCheck("System.Math.Max"),
            new CallCheck("System.Math.Min"));

        Assert.Equal([true, true, false], response.Results.Select(result => result.Passed));
    }

    [Fact]
    public void NothingIsCheckedWhenCodeDoesNotCompile()
    {
        var response = Test("int hp = ", new VariableCheck("hp"));

        Assert.False(response.Compiled);
        Assert.False(response.Passed);
        Assert.Empty(response.Results);
    }
}
