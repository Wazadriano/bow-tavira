# Agents BYAN - Ecosysteme Complet

## Core Module (Foundation)

| Agent | Persona | Role |
|-------|---------|------|
| **hermes** | Dispatcher | Routeur universel, point d'entree |
| **bmad-master** | Orchestrateur | Execute workflows et tasks |
| **yanstaller** | Installeur | Installation intelligente BYAN |

## BMB Module (Builders)

| Agent | Persona | Role |
|-------|---------|------|
| **byan** | Builder | Createur d'agents via interview (12 questions, 64 mantras) |
| **agent-builder** | Constructeur | Expert en construction d'agents |
| **marc** | Specialiste | Integration GitHub Copilot |
| **rachid** | Specialiste | Deploiement NPM/NPX |
| **carmack** | Optimiseur | Optimisation tokens |
| **patnote** | Gestionnaire | Mises a jour et conflits |

## BMM Module (SDLC - Software Development Lifecycle)

| Agent | Persona | Role |
|-------|---------|------|
| **analyst** | Mary | Analyse business, etude de marche, brief |
| **architect** | Winston | Design systeme, tech stack, architecture |
| **dev** | Amelia | Implementation, coding, ultra-succincte |
| **pm** | John | Product management, PRD, roadmap |
| **sm** | Bob | Scrum master, sprint planning, backlog |
| **quinn** | Quinn | QA engineer, tests, couverture |
| **tech-writer** | Paige | Documentation, guides, clarity |
| **ux-designer** | Sally | UX/UI design, empathie utilisateur |
| **quick-flow-solo-dev** | Barry | Dev rapide brownfield |

## CIS Module (Creative Innovation & Strategy)

| Agent | Persona | Role |
|-------|---------|------|
| **brainstorming-coach** | Carson | Ideation, "YES AND" energy |
| **creative-problem-solver** | Dr. Quinn | Resolution de problemes |
| **design-thinking-coach** | Maya | Design thinking |
| **innovation-strategist** | Victor | Strategie innovation |
| **presentation-master** | Caravaggio | Presentations, slides |
| **storyteller** | Sophia | Storytelling, narratives |

## TEA Module (Test Engineering & Architecture)

| Agent | Persona | Role |
|-------|---------|------|
| **tea** | Murat | Master test architect (ATDD, NFR, CI/CD) |

## BOW Module (Projet Book of Work)

| Agent | Persona | Role |
|-------|---------|------|
| **bow-product-manager** | BOW-PM | Specs metier, regles RG-BOW-*, glossaire, 5 modules |
| **bow-architect** | BOW-ARCH | Architecture systeme, Docker/Traefik, ADR |
| **bow-backend-expert** | BOW-BACK | Laravel 11, PostgreSQL, 150+ endpoints, TDD Pest |
| **bow-frontend-expert** | BOW-FRONT | Next.js 15, Shadcn/ui, Zustand, 30+ pages |
| **bow-qa-tester** | BOW-QA | Tests TDD, edge cases, permissions matrix |
| **bow-devsecops** | BOW-SEC | Securite OWASP, hardening Docker, secrets |
| **bow-github-expert** | BOW-GH | CI/CD GitHub Actions, branching, PRs |
| **bow-excel-specialist** | BOW-XLS | Import/export Excel, multi-sheet, parsing |
| **bow-infra-deployer** | BOW-INFRA | Deploiement OVH, staging, production, SMTP, DNS, SSL, monitoring |

## Workflows Cles

| Workflow | Description |
|----------|-------------|
| `create-prd` | Creer un Product Requirements Document |
| `create-architecture` | Concevoir l'architecture technique |
| `create-epics-and-stories` | Decouper en epics et user stories |
| `sprint-planning` | Planifier un sprint |
| `dev-story` | Developper une story |
| `code-review` | Revoir du code |
| `quick-spec` | Spec rapide conversationnelle |
| `quick-dev` | Dev rapide (brownfield) |

## Comment Invoquer un Agent

Dans Claude Code, demande simplement:
- "Je veux creer une architecture" → Hermes recommande `architect`
- "Analyse ce projet" → Hermes recommande `analyst`
- "Cree un nouvel agent" → Hermes recommande `byan`
