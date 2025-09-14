# Media Budget Optimization - Excel Implementation Guide

## A. Table Layout for Excel (Mac)

Copy the left column into column A and put the B-cell contents or formulas into column B exactly as shown.

| A (Label) | B (Cell content / formula) |
|-----------|----------------------------|
| **Decision Variables** | |
| Social media $ (S) | 1000 ← B2 (starting guess, e.g. 1000) |
| Television $ (T) | 1000 ← B3 (starting guess) |
| | |
| **Total Spend** | =B2 + B3 ← B5 |
| **Total Reach (people)** | =50*B2 + 40*B3 ← B6 |
| | |
| **Budget available ($)** | 50000 ← B10 (requirement cell) |
| **Max TV percent** | 0.7 ← B11 |
| **Max TV $ limit** | =B10 * B11 ← B12 (automatically 35000) |
| **Required Reach (people)** | 1500000 ← B13 |

**Optional labels:** Put "Budget available", "Max TV %", "Max TV $ limit", "Required Reach" in column A next to the B-values.

---

## B. Model A — Maximize Reach (uses whole budget)

Use this if the company will spend the full budget and wants the best reach.

### Formulas / constraints used

- **Objective:** Maximize Total Reach (B6)
- **Decision variables:** B2:B3 (S and T)
- **Constraints:**
  - B2 + B3 = B10 (use all budget) — use = if they must allocate exactly the $50,000.
  - Or use <= B10 if they may spend up to $50,000.
  - B3 <= B12 (no more than 70% on TV)
  - B2 >= 0, B3 >= 0

### Solver setup (Mac Excel)

1. **Data > Solver…**
2. **Set Objective:** select B6
3. **To:** choose Max
4. **By Changing Variable Cells:** $B$2:$B$3
5. **Add constraints** (click Add for each):
   - $B$2 + $B$3 = $B$10 (or <= if allowed)
   - $B$3 <= $B$12
   - $B$2 >= 0
   - $B$3 >= 0
6. **Select a Solving Method:** Simplex LP
7. **Click Solve → Keep Solver Solution → OK**

### Expected result (because social gives more reach per $)

- B2 (Social) = 50000
- B3 (TV) = 0
- B6 (Reach) = 50 * 50000 = 2,500,000 people

**Explanation:** Social media gives 50 people per $, TV only 40 per $. To maximize reach with a fixed budget you put everything into social media, subject to the TV cap (which is not binding here).

---

## C. Model B — Minimize Spend required to reach at least 1.5M

Use this if the goal is to reach at least 1.5 million while spending as little as possible.

### Formulas / constraints used

- **Objective:** Minimize Total Spend (B5)
- **Decision variables:** B2:B3
- **Constraints:**
  - 50*B2 + 40*B3 >= B13 (reach at least 1,500,000)
  - B3 <= B12 (TV ≤ 70% of budget limit)
  - B2 >= 0, B3 >= 0
  - (Optional) B5 <= B10 if there is a hard upper budget cap you must not exceed.

### Solver setup (Mac Excel)

1. **Data > Solver…**
2. **Set Objective:** B5
3. **To:** choose Min
4. **By Changing Variable Cells:** $B$2:$B$3
5. **Add constraints:**
   - 50*$B$2 + 40*$B$3 >= $B$13
   - $B$3 <= $B$12
   - $B$2 >= 0
   - $B$3 >= 0
   - (Optional) $B$5 <= $B$10
6. **Solving Method:** Simplex LP
7. **Click Solve → Keep Solver Solution → OK**

### Expected result (math quick-check)

The most cost-efficient channel is social media (50 people per $ vs 40). So the cheapest way to reach 1,500,000 is all social media:

- Needed social-only spend = 1,500,000 / 50 = $30,000
- So B2 = 30000, B3 = 0, B5 = 30000, B6 = 1,500,000

This satisfies the TV cap and is ≤ the $50,000 budget. Solver will find this minimal spend.

---

## D. Notes, pitfalls and teacher requirements

- If the teacher requires you to use the full $50,000, use **Model A** (maximize reach with B2 + B3 = 50000).
- If the teacher wants minimum spending to reach 1.5M, use **Model B** (minimize B5 with reach constraint).
- If a mixed requirement exists (e.g., must spend at least X on TV), add constraint B3 >= X.
- If TV budget must be integer units (unlikely here) you can set integer constraints — but money is continuous so do not add integer unless stated.

---

## E. Final checklist (before Solve)

- [ ] **Formulas:** B5 = B2 + B3, B6 = 50*B2 + 40*B3, B12 = B10 * B11
- [ ] **Requirement cells not blank:** B10 = 50000, B11 = 0.7, B13 = 1500000
- [ ] **Solver Objective, direction, variables and constraints set exactly as above**
- [ ] **Use Simplex LP**

---

## Quick Reference Table for Excel Setup

| Cell | Content | Purpose |
|------|---------|---------|
| B2 | 1000 | Social media budget (starting guess) |
| B3 | 1000 | Television budget (starting guess) |
| B5 | =B2 + B3 | Total spend formula |
| B6 | =50*B2 + 40*B3 | Total reach formula |
| B10 | 50000 | Available budget |
| B11 | 0.7 | Max TV percentage |
| B12 | =B10 * B11 | Max TV dollar limit |
| B13 | 1500000 | Required reach target |

## Solver Parameters Summary

### Model A (Maximize Reach)
- **Objective:** B6 (Max)
- **Variables:** B2:B3
- **Constraints:** B2+B3=B10, B3≤B12, B2≥0, B3≥0

### Model B (Minimize Spend)
- **Objective:** B5 (Min)
- **Variables:** B2:B3
- **Constraints:** 50*B2+40*B3≥B13, B3≤B12, B2≥0, B3≥0
